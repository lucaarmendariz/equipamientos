<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once '../klaseak/ekipamenduak.php';
require_once '../klaseak/inbentarioa.php';
require_once 'conexion.php';
require_once 'apiKey.php';

ApiKeyManager::requireApiKey(); // Valida API key
$conn = DB::getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$method = $_SERVER['REQUEST_METHOD'];

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

try {
    switch ($method) {
        case 'GET':
            $id = intval($_GET['id'] ?? 0);

            if ($id > 0) {
                $equipo = Ekipamendua::getById($id);
                if (!$equipo)
                    throw new Exception("Equipo no encontrado");

                // Obtener nombre de categoría
                $stmt = $conn->prepare("SELECT izena FROM kategoria WHERE id = ?");
                $idKategoria = $equipo->getIdKategoria();
                $stmt->bind_param("i", $idKategoria);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $data = $equipo->toArray();
                $data['kategoria'] = $row['izena'] ?? null;

                $response['success'] = true;
                $response['data'] = $data;

            } else {
                // Listar todos los equipamientos con nombre de categoría
                $sql = "SELECT e.id, e.izena, e.deskribapena, e.marka, e.modelo, 
        (SELECT COUNT(*) FROM inbentarioa i WHERE i.idEkipamendu = e.id) AS stock,
        e.idKategoria, k.izena AS kategoria
        FROM ekipamendua e
        LEFT JOIN kategoria k ON e.idKategoria = k.id
        ORDER BY e.izena";

                $result = $conn->query($sql);
                if (!$result)
                    throw new Exception("Error al obtener equipos: " . $conn->error);

                $equipos = [];
                while ($row = $result->fetch_assoc()) {
                    $equipos[] = $row;
                }

                $response['success'] = true;
                $response['data'] = $equipos;
            }
            break;



        case 'POST':
            // Crear nuevo equipamiento
            $nombre = trim($input['izena'] ?? '');
            $descripcion = trim($input['deskribapena'] ?? '');
            $marka = trim($input['marka'] ?? '') ?: null;
            $modelo = trim($input['modelo'] ?? '') ?: null;
            $stock = intval($input['stock'] ?? 0); // si no hay stock, 0
            $idKategoria = intval($input['idKategoria'] ?? 0);

            if (!$nombre)
                throw new Exception("El nombre del equipo es obligatorio");

            $stmt = $conn->prepare("INSERT INTO ekipamendua (izena, deskribapena, marka, modelo, stock, idKategoria) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt)
                throw new Exception("Error en prepare: " . $conn->error);
            $stmt->bind_param("ssssii", $nombre, $descripcion, $marka, $modelo, $stock, $idKategoria);
            $stmt->execute();
            $nuevoId = $stmt->insert_id;
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Equipo creado correctamente";
            $response['data'] = ['id' => $nuevoId, 'stock' => $stock ?? 0];
            break;

        case 'PUT':
            // Editar equipamiento (no permite cambiar stock)
            $id = intval($input['id'] ?? 0);
            if (!$id)
                throw new Exception("ID del equipo requerido");

            $nombre = trim($input['izena'] ?? '');
            $descripcion = trim($input['deskribapena'] ?? '');
            $marka = trim($input['marka'] ?? '') ?: null;
            $modelo = trim($input['modelo'] ?? '') ?: null;
            $idKategoria = intval($input['idKategoria'] ?? 0);

            $stmt = $conn->prepare("UPDATE ekipamendua SET izena = ?, deskribapena = ?, marka = ?, modelo = ?, idKategoria = ? WHERE id = ?");
            if (!$stmt)
                throw new Exception("Error en prepare: " . $conn->error);
            $stmt->bind_param("ssssii", $nombre, $descripcion, $marka, $modelo, $idKategoria, $id);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Equipo actualizado correctamente";
            break;

        case 'DELETE':
            $id = intval($input['id'] ?? 0);
            if (!$id)
                throw new Exception("ID del equipo requerido para eliminar");

            // ✅ Verificar si existen etiquetas
            $stmtCheck = $conn->prepare("SELECT COUNT(*) as cnt FROM inbentarioa WHERE idEkipamendu = ?");
            $stmtCheck->bind_param("i", $id);
            $stmtCheck->execute();
            $row = $stmtCheck->get_result()->fetch_assoc();
            $stmtCheck->close();

            if ($row['cnt'] > 0) {
                // Si hay etiquetas, no se puede eliminar
                $response['success'] = false;
                $response['message'] = "No se puede eliminar el equipo. Tiene " . $row['cnt'] . " etiquetas asociadas.";
                echo json_encode($response);
                exit;
            }

            // ✅ Si no hay etiquetas, eliminar normalmente
            $stmt = $conn->prepare("DELETE FROM ekipamendua WHERE id = ?");
            if (!$stmt)
                throw new Exception("Error en prepare: " . $conn->error);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Equipo eliminado correctamente";
            break;


        default:
            throw new Exception("Método no soportado");
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
$conn->close();
exit;
