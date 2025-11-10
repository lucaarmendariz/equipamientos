<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once 'conexion.php'; // conexión $mysqli
require_once 'apiKey.php';   // validación de API key

// 🔒 Requiere API Key para acceder a cualquier funcionalidad
ApiKeyManager::requireApiKey();

// Recibir datos JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? 'GET';

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

try {
    switch (strtoupper($action)) {

        // =================================================
        // LISTAR GELAS
        // =================================================
        case 'GET':
            $query = "SELECT id, izena, taldea FROM gela ORDER BY izena ASC";
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
        // AÑADIR GELA
        // =================================================
        case 'POST':
            $izena = $input['izena'] ?? null;
            $taldea = $input['taldea'] ?? null;

            if (!$izena) throw new Exception("Falta el nombre de la gela");

            $stmt = $mysqli->prepare("INSERT INTO gela (izena, taldea) VALUES (?, ?)");
            $stmt->bind_param("ss", $izena, $taldea);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Gela añadida correctamente";
            break;

        // =================================================
        // ACTUALIZAR GELA
        // =================================================
        case 'PUT':
            $id = $input['id'] ?? null;
            $izena = $input['izena'] ?? null;
            $taldea = $input['taldea'] ?? null;

            if (!$id || !$izena)
                throw new Exception("Faltan campos obligatorios");

            $stmt = $mysqli->prepare("UPDATE gela SET izena = ?, taldea = ? WHERE id = ?");
            $stmt->bind_param("ssi", $izena, $taldea, $id);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Gela actualizada correctamente";
            break;

        // =================================================
        // ELIMINAR GELA
        // =================================================
        case 'DELETE':
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
