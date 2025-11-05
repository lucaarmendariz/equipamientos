<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once '../klaseak/inbentarioa.php';
require_once 'conexion.php';

$conn = DB::getConnection();

$input = json_decode(file_get_contents('php://input'), true);
$action = strtoupper(trim($input['action'] ?? 'GET'));

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

try {
    switch ($action) {

        // =================================================
        // LISTAR INVENTARIO
        // =================================================
        case 'GET':
            $sql = "
                SELECT 
                    i.etiketa AS etiketa,
                    e.id AS idEkipamendu,
                    e.izena AS ekipamendua,
                    g.taldea AS gela
                FROM inbentarioa i
                JOIN ekipamendua e ON i.idEkipamendu = e.id
                LEFT JOIN kokalekua k ON i.etiketa = k.etiketa
                LEFT JOIN gela g ON k.idGela = g.id
                ORDER BY e.izena;
            ";

            $result = $conn->query($sql);
            if (!$result) throw new Exception("Error al obtener inventario: " . $conn->error);

            $inventario = [];
            while ($row = $result->fetch_assoc()) {
                $inventario[] = $row;
            }

            $response['success'] = true;
            $response['data'] = $inventario;
            break;

        // =================================================
        // AÑADIR ITEM AL INVENTARIO
        // =================================================
        case 'POST':
            $etiketa = $input['etiketa'] ?? '';
            $idEkipamendu = (int) ($input['idEkipamendu'] ?? 0);
            $fecha = $input['erosketaData'] ?? date('Y-m-d');

            if (!$idEkipamendu)
                throw new Exception("Falta el ID del equipo");

            $item = Inbentarioa::create($etiketa, $idEkipamendu, $fecha);
            if (!$item)
                throw new Exception("Error al añadir item al inventario");

            $response['success'] = true;
            $response['data'] = $item->toArray();
            break;

        // =================================================
        // ACTUALIZAR ITEM DEL INVENTARIO
        // =================================================
        case 'PUT':
            $etiketa = $input['etiketa'] ?? '';
            $idEkipamendu = (int) ($input['idEkipamendu'] ?? 0);
            $fecha = $input['erosketaData'] ?? '';

            if (!$etiketa || !$idEkipamendu || !$fecha) {
                throw new Exception("Faltan campos obligatorios para actualizar");
            }

            $stmt = $conn->prepare("UPDATE inbentarioa SET idEkipamendu = ?, erosketadata = ? WHERE etiketa = ?");
            $stmt->bind_param("iss", $idEkipamendu, $fecha, $etiketa);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Inventario actualizado correctamente";
            break;

        // =================================================
        // ELIMINAR ITEM DEL INVENTARIO Y ACTUALIZAR STOCK
        // =================================================
        case 'DELETE':
            $etiketa = $input['etiketa'] ?? '';
            if (!$etiketa)
                throw new Exception("Falta la etiqueta para eliminar");

            // Obtener el item como objeto
            $item = Inbentarioa::getByEtiketa($etiketa);
            if (!$item)
                throw new Exception("Item no encontrado");

            $idEkipamendu = $item->getIdEkipamendu();

            // Transacción para asegurar consistencia
            $conn->begin_transaction();
            try {
                // Eliminar etiqueta
                $item->delete();

                // Actualizar stock (evitar negativo)
                $stmt = $conn->prepare("UPDATE ekipamendua SET stock = GREATEST(stock - 1, 0) WHERE id = ?");
                $stmt->bind_param("i", $idEkipamendu);
                $stmt->execute();
                $stmt->close();

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }

            // Obtener stock actualizado
            $stmt = $conn->prepare("SELECT stock FROM ekipamendua WHERE id = ?");
            $stmt->bind_param("i", $idEkipamendu);
            $stmt->execute();
            $result = $stmt->get_result();
            $nuevo_stock = $result->fetch_assoc()['stock'] ?? 0;
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Etiqueta eliminada y stock actualizado";
            $response['idEkipamendu'] = $idEkipamendu;
            $response['nuevo_stock'] = $nuevo_stock;
            break;

        // =================================================
        // ACTUALIZAR STOCK Y CREAR NUEVAS ETIQUETAS
        // =================================================
        case 'STOCK':
            $idEkipamendu = intval($input['idEkipamendu'] ?? 0);
            $cantidad = intval($input['cantidad'] ?? 0);
            if ($idEkipamendu <= 0 || $cantidad <= 0)
                throw new Exception('Datos inválidos (idEkipamendu, cantidad)');

            // Obtener equipo y stock actual
            $stmt = $conn->prepare("SELECT izena, stock FROM ekipamendua WHERE id = ?");
            $stmt->bind_param("i", $idEkipamendu);
            $stmt->execute();
            $result = $stmt->get_result();
            $equipo = $result->fetch_assoc();
            $stmt->close();

            if (!$equipo) throw new Exception("Equipo no encontrado");

            $stock_actual = intval($equipo['stock']);
            $hoy = date('Y-m-d');
            $nuevas_etiquetas = [];

            for ($i = 0; $i < $cantidad; $i++) {
                $rand = function_exists('random_int') ? random_int(1, 9999) : mt_rand(1, 9999);
                $nuevo_codigo = 'ETK' . sprintf('%04d', $rand);

                // Evitar duplicados
                $check = $conn->prepare("SELECT 1 FROM inbentarioa WHERE etiketa = ?");
                $check->bind_param("s", $nuevo_codigo);
                $check->execute();
                $exists = $check->get_result()->num_rows > 0;
                $check->close();

                if ($exists) {
                    $i--;
                    continue;
                }

                $nuevo_item = Inbentarioa::create($nuevo_codigo, $idEkipamendu, $hoy);
                if (!$nuevo_item)
                    throw new Exception("Error al insertar etiqueta $nuevo_codigo");

                $nuevas_etiquetas[] = $nuevo_codigo;
            }

            // Actualizar stock
            $nuevo_stock = $stock_actual + $cantidad;
            $update = $conn->prepare("UPDATE ekipamendua SET stock = ? WHERE id = ?");
            $update->bind_param("ii", $nuevo_stock, $idEkipamendu);
            $update->execute();
            $update->close();

            $response['success'] = true;
            $response['message'] = 'Stock actualizado y etiquetas creadas correctamente';
            $response['nuevas_etiquetas'] = $nuevas_etiquetas;
            $response['nuevo_stock'] = $nuevo_stock;
            break;

        default:
            throw new Exception("Acción no reconocida");
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Error: " . $e->getMessage();
}

echo json_encode($response);
$conn->close();
exit;
