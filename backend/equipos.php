<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php'; // Asegúrate de tener aquí tu conexión $mysqli

// Recibir datos JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? null;

$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Falta el parámetro action']);
    exit;
}

try {
    switch ($action) {

        // ============================================================
        // 🟢 INSERTAR EQUIPO
        // ============================================================
        case 'insert':
            $nombre      = $input['nombre'] ?? null;
            $descripcion = $input['deskribapena'] ?? null;
            $marca       = $input['marca'] ?? '';
            $modelo      = $input['modelo'] ?? '';
            $stock       = isset($input['stock']) ? (int)$input['stock'] : 0;
            $idCategoria = isset($input['idKategoria']) ? (int)$input['idKategoria'] : null;

            if (!$nombre || !$descripcion || !$idCategoria) {
                throw new Exception("Faltan campos obligatorios");
            }

            $mysqli->begin_transaction();

            // Obtener el último ID
            $result = $mysqli->query("SELECT MAX(id) AS last_id FROM ekipamendua");
            $row = $result->fetch_assoc();
            $idEquipo = ($row['last_id'] ?? 0) + 1;

            // Insertar en ekipamendua
            $stmt = $mysqli->prepare("INSERT INTO ekipamendua (id, izena, deskribapena, marka, modelo, stock, idKategoria) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssiii", $idEquipo, $nombre, $descripcion, $marca, $modelo, $stock, $idCategoria);
            $stmt->execute();
            $stmt->close();

            // Generar etiqueta
            $etiketa = 'E' . str_pad($idEquipo, 6, '0', STR_PAD_LEFT);
            $fechaHoy = date('Y-m-d');

            // Insertar en inbentarioa
            $stmtInv = $mysqli->prepare("INSERT INTO inbentarioa (etiketa, idEkipamendu, erosketaData) VALUES (?, ?, ?)");
            $stmtInv->bind_param("sis", $etiketa, $idEquipo, $fechaHoy);
            $stmtInv->execute();
            $stmtInv->close();


            $mysqli->commit();

            $response = [
                'success' => true,
                'message' => 'Equipo insertado correctamente',
                'data' => [
                    'id' => $idEquipo,
                    'etiketa' => $etiketa
                ]
            ];
            break;

        // ============================================================
        // 🟠 LISTAR EQUIPOS
        // ============================================================
        case 'list':
            $query = "SELECT id, izena, stock, marka, modelo, deskribapena, idKategoria FROM ekipamendua ORDER BY id DESC LIMIT 50";
            $result = $mysqli->query($query);

            if ($result) {
                $equipamientos = [];
                while ($row = $result->fetch_assoc()) {
                    $equipamientos[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $equipamientos;
            } else {
                throw new Exception("Error en la consulta: " . $mysqli->error);
            }
            break;

        // ============================================================
        // 🟡 ACTUALIZAR EQUIPO
        // ============================================================
        case 'update':
            $id          = isset($input['id']) ? (int)$input['id'] : null;
            $nombre      = $input['nombre'] ?? null;
            $descripcion = $input['deskribapena'] ?? null;
            $marca       = $input['marca'] ?? '';
            $modelo      = $input['modelo'] ?? '';
            $stock       = isset($input['stock']) ? (int)$input['stock'] : 0;
            $idCategoria = isset($input['idKategoria']) ? (int)$input['idKategoria'] : null;

            if (!$id || !$nombre || !$descripcion || !$idCategoria) {
                throw new Exception("Faltan campos obligatorios para actualizar");
            }

            $stmt = $mysqli->prepare("UPDATE ekipamendua SET izena=?, deskribapena=?, marka=?, modelo=?, stock=?, idKategoria=? WHERE id=?");
            $stmt->bind_param("sssiiii", $nombre, $descripcion, $marca, $modelo, $stock, $idCategoria, $id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = "Equipo actualizado correctamente";
            } else {
                $response['message'] = "No se actualizó ningún registro (verifica el ID)";
            }

            $stmt->close();
            break;

        // ============================================================
        // 🔴 ELIMINAR EQUIPO
        // ============================================================
        case 'delete':
            $id = isset($input['id']) ? (int)$input['id'] : null;
            if (!$id) {
                throw new Exception("Falta el ID para eliminar");
            }

            $mysqli->begin_transaction();

            // Eliminar de kokalekua e inbentarioa primero (si hay referencias)
            $stmtKok = $mysqli->prepare("DELETE FROM kokalekua WHERE etiketa IN (SELECT etiketa FROM inbentarioa WHERE idEkipamendu=?)");
            $stmtKok->bind_param("i", $id);
            $stmtKok->execute();
            $stmtKok->close();

            $stmtInv = $mysqli->prepare("DELETE FROM inbentarioa WHERE idEkipamendu=?");
            $stmtInv->bind_param("i", $id);
            $stmtInv->execute();
            $stmtInv->close();

            // Finalmente eliminar el equipo
            $stmtEq = $mysqli->prepare("DELETE FROM ekipamendua WHERE id=?");
            $stmtEq->bind_param("i", $id);
            $stmtEq->execute();
            $stmtEq->close();

            $mysqli->commit();

            $response['success'] = true;
            $response['message'] = "Equipo eliminado correctamente";
            break;

        // 🟣 OBTENER EQUIPO POR ID
        case 'getById':
            $id = isset($input['id']) ? (int)$input['id'] : null;
            if (!$id) throw new Exception("Falta el ID para obtener el equipo");

            $stmt = $mysqli->prepare("SELECT id, izena, stock, marka, modelo, deskribapena, idKategoria FROM ekipamendua WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $equipo = $result->fetch_assoc();
            $stmt->close();

            if ($equipo) {
                $response['success'] = true;
                $response['data'] = $equipo;
            } else {
                $response['success'] = false;
                $response['message'] = "Equipo no encontrado";
            }
            break;
        // ============================================================
        // ❌ ACCIÓN DESCONOCIDA
        // ============================================================
        default:
            throw new Exception("Acción no válida: $action");
    }
} catch (Exception $e) {
    $mysqli->rollback();
    $response['success'] = false;
    $response['message'] = "Error: " . $e->getMessage();
}

$mysqli->close();
echo json_encode($response);
exit;
?>
