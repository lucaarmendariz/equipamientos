<?php
declare(strict_types=1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once '../klaseak/inbentarioa.php';
require_once 'conexion.php';
require_once 'apiKey.php';

ApiKeyManager::requireApiKey(); // <-- aquí se valida la sesión por API key
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

        // =====================
        // LISTAR INVENTARIO
        // =====================
        case 'GET':
            $sql = "
    SELECT 
        i.etiketa AS etiketa,
        e.id AS idEkipamendu,
        e.izena AS ekipamendua,
        g.taldea AS gela
    FROM inbentarioa i
    JOIN ekipamendua e ON i.idEkipamendu = e.id
    LEFT JOIN kokalekua k ON i.etiketa = k.etiketa AND (k.amaieraData IS NULL OR k.amaieraData = '')
    LEFT JOIN gela g ON k.idGela = g.id
    ORDER BY e.izena, i.etiketa;
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

        // =============================
        // AÑADIR ITEM AL INVENTARIO
        // =============================
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

        // ==================================
        // ACTUALIZAR ITEM DEL INVENTARIO
        // ==================================
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
        // ELIMINAR ETIQUETAS MASIVAS
        // =================================================
        case 'DELETE_MULTIPLE':
            $etiquetas = $input['etiquetas'] ?? [];
            if (!is_array($etiquetas) || count($etiquetas) === 0)
                throw new Exception("No se enviaron etiquetas para eliminar");

            $conn->begin_transaction();

            try {
                // 1. Contar cuántas etiquetas de cada equipo se eliminarán
                $placeholders = implode(',', array_fill(0, count($etiquetas), '?'));
                $types = str_repeat('s', count($etiquetas));

                $stmt = $conn->prepare("SELECT idEkipamendu, COUNT(*) as cnt FROM inbentarioa WHERE etiketa IN ($placeholders) GROUP BY idEkipamendu");
                $stmt->bind_param($types, ...$etiquetas);
                $stmt->execute();
                $result = $stmt->get_result();

                $equipos = [];
                while ($row = $result->fetch_assoc()) {
                    $equipos[intval($row['idEkipamendu'])] = intval($row['cnt']);
                }
                $stmt->close();

                if (empty($equipos)) {
                    $conn->commit();
                    $response['success'] = true;
                    $response['message'] = "No se encontraron etiquetas para eliminar";
                    break;
                }

                // 2. Eliminar etiquetas
                $stmt = $conn->prepare("DELETE FROM inbentarioa WHERE etiketa IN ($placeholders)");
                $stmt->bind_param($types, ...$etiquetas);
                $stmt->execute();
                $stmt->close();

                // 3. Actualizar stock y obtener stock final
                $stocks_finales = [];
                foreach ($equipos as $idEkipamendu => $cantidad) {
                    // Actualizar stock
                    $update = $conn->prepare("UPDATE ekipamendua SET stock = GREATEST(stock - ?, 0) WHERE id = ?");
                    $update->bind_param("ii", $cantidad, $idEkipamendu);
                    $update->execute();
                    $update->close();

                    // Obtener stock final
                    $stmt = $conn->prepare("SELECT stock FROM ekipamendua WHERE id = ?");
                    $stmt->bind_param("i", $idEkipamendu);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $nuevo_stock = $result->fetch_assoc()['stock'] ?? 0;
                    $stmt->close();

                    $stocks_finales[$idEkipamendu] = $nuevo_stock;
                }

                $conn->commit();

                $response['success'] = true;
                $response['message'] = count($etiquetas) . " etiquetas eliminadas correctamente";
                $response['stocks_finales'] = $stocks_finales;

            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }

            break;

       


        // ============================================
        // ACTUALIZAR STOCK Y CREAR NUEVAS ETIQUETAS
        // ============================================
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

            // 🔹 Obtener el último número usado en las etiquetas
            $sql = "SELECT etiketa FROM inbentarioa WHERE etiketa LIKE 'ETK%' ORDER BY etiketa DESC LIMIT 1";
            $result = $conn->query($sql);
            $last_num = 0;
            if ($row = $result->fetch_assoc()) {
                $last_code = $row['etiketa'];       // Ej: "ETK0007"
                $last_num = intval(substr($last_code, 3)); // Extrae "7"
            }

            // 🔹 Crear nuevas etiquetas autoincrementales
            for ($i = 0; $i < $cantidad; $i++) {
                $last_num++;
                $nuevo_codigo = 'ETK' . sprintf('%04d', $last_num);

                $nuevo_item = Inbentarioa::create($nuevo_codigo, $idEkipamendu, $hoy);
                if (!$nuevo_item)
                    throw new Exception("Error al insertar etiqueta $nuevo_codigo");

                $nuevas_etiquetas[] = $nuevo_codigo;
            }


            // Actualizar stock
            $nuevo_stock = $cantidad;
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
