<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once '../klaseak/inbentarioa.php';

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
            $inventario = [];
            $conn = DB::getConnection();

            foreach (Inbentarioa::getAll() as $item) {
                $row = $item->toArray();

                // Obtener el nombre del equipo usando el idEkipamendu
                $stmt = $conn->prepare("SELECT izena FROM ekipamendua WHERE id = ?");
                $stmt->bind_param("i", $row['idEkipamendu']);
                $stmt->execute();
                $result = $stmt->get_result();
                $equipoRow = $result->fetch_assoc();
                $stmt->close();

                $row['equipo'] = $equipoRow['izena'] ?? 'Desconocido';
                $inventario[] = $row;
            }

            $response['success'] = true;
            $response['data'] = $inventario;
            break;


        // =================================================
        // AÑADIR ITEM AL INVENTARIO
        // =================================================
        case 'POST':
            $idEkipamendu = (int) ($input['idEkipamendu'] ?? 0);
            $fecha = $input['erosketaData'] ?? date('Y-m-d');

            if (!$idEkipamendu)
                throw new Exception("Falta el ID del equipo");

            // Generar etiqueta automática
            $etiketa = 'I' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

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

            $conn = DB::getConnection();
            $stmt = $conn->prepare("UPDATE inbentarioa SET idEkipamendu = ?, erosketadata = ? WHERE etiketa = ?");
            $stmt->bind_param("iss", $idEkipamendu, $fecha, $etiketa);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Inventario actualizado correctamente";
            break;

        // =================================================
        // ELIMINAR ITEM DEL INVENTARIO
        // =================================================
        case 'DELETE':
            $etiketa = $input['etiketa'] ?? '';
            if (!$etiketa)
                throw new Exception("Falta la etiqueta para eliminar");

            $item = Inbentarioa::getByEtiketa($etiketa);
            if (!$item)
                throw new Exception("Item no encontrado");

            $item->delete();
            $response['success'] = true;
            $response['message'] = "Inventario eliminado correctamente";
            break;

        default:
            throw new Exception("Acción no reconocida");
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Error: " . $e->getMessage();
}

echo json_encode($response);
exit;
