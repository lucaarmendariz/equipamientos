<?php
// kategoriak.php
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
        // LISTAR CATEGORÍAS
        // =================================================
        case 'list':
            $query = "SELECT id, izena FROM kategoria ORDER BY izena ASC";
            $result = $mysqli->query($query);

            if ($result) {
                $categorias = [];
                while ($row = $result->fetch_assoc()) {
                    $categorias[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $categorias;
            } else {
                $response['message'] = "Error en la consulta: " . $mysqli->error;
            }
            break;

        // =================================================
        // AÑADIR CATEGORÍA (opcional)
        // =================================================
        case 'add':
            $nombre = $input['izena'] ?? null;
            if (!$nombre) throw new Exception("Falta el nombre de la categoría");

            $stmt = $mysqli->prepare("INSERT INTO kategoria (izena) VALUES (?)");
            $stmt->bind_param("s", $nombre);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Categoría añadida correctamente";
            break;

        // =================================================
        // ACTUALIZAR CATEGORÍA (opcional)
        // =================================================
        case 'update':
            $id = $input['id'] ?? null;
            $nombre = $input['izena'] ?? null;
            if (!$id || !$nombre) throw new Exception("Faltan campos obligatorios");

            $stmt = $mysqli->prepare("UPDATE kategoria SET izena = ? WHERE id = ?");
            $stmt->bind_param("si", $nombre, $id);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Categoría actualizada correctamente";
            break;

        // =================================================
        // ELIMINAR CATEGORÍA (opcional)
        // =================================================
        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) throw new Exception("Falta el ID de la categoría");

            $stmt = $mysqli->prepare("DELETE FROM kategoria WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Categoría eliminada correctamente";
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
