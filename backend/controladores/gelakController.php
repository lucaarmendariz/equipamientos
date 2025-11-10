<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php'; // conexión $mysqli

// Recibir datos JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'list';

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

try {
    switch ($action) {

        // =================================================
        // LISTAR GELAS
        // =================================================
        case 'GET':
            $query = "SELECT id, izena FROM gela ORDER BY izena ASC";
            $result = $mysqli->query($query);

            if ($result) {
                $gelas = [];
                while ($row = $result->fetch_assoc()) {
                    $gelas[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $gelas;
            } else {
                $response['message'] = "Error en la consulta: " . $mysqli->error;
            }
            break;

        // =================================================
        // AÑADIR GELA (opcional)
        // =================================================
        case 'POST':
            $nombre = $input['izena'] ?? null;
            if (!$nombre) throw new Exception("Falta el nombre de la gela");

            $stmt = $mysqli->prepare("INSERT INTO gela (izena) VALUES (?)");
            $stmt->bind_param("s", $nombre);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Gela añadida correctamente";
            break;

        // =================================================
        // ACTUALIZAR GELA (opcional)
        // =================================================
        case 'PUT':
            $id = $input['id'] ?? null;
            $nombre = $input['izena'] ?? null;
            if (!$id || !$nombre) throw new Exception("Faltan campos obligatorios");

            $stmt = $mysqli->prepare("UPDATE gela SET izena = ? WHERE id = ?");
            $stmt->bind_param("si", $nombre, $id);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Gela actualizada correctamente";
            break;

        // =================================================
        // ELIMINAR GELA (opcional)
        // =================================================
        case "DELETE":
            $id = $input['id'] ?? null;
            if (!$id) throw new Exception("Falta el ID de la gela");

            $stmt = $mysqli->prepare("DELETE FROM gela WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Gela eliminada correctamente";
            break;

        default:
            $response['message'] = "Acción no reconocida";

    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Error: " . $e->getMessage();
}

$mysqli->close();
echo json_encode($response);
exit;
