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

require_once '../klaseak/kategoriak.php';
require_once 'conexion.php';
require_once 'apiKey.php';

ApiKeyManager::requireApiKey(); // <-- aquí se valida la sesión por API key
function respond(bool $success, array $data = [], string $message = ''): void
{
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];

    // Obtener datos JSON si existe
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    switch ($method) {
        // ================================
        // LISTAR CATEGORÍAS
        // ================================
        case 'GET':
            $categorias = Kategoria::getAll();
            $arrayCategorias = array_map(fn($c) => $c->toArray(), $categorias);
            respond(true, $arrayCategorias);
            break;

        // ================================
        // CREAR NUEVA CATEGORÍA
        // ================================
        case 'POST':
            $nombre = $input['izena'] ?? '';
            if (!$nombre)
                respond(false, [], 'Falta el nombre de la categoría');

            $categoria = Kategoria::create($nombre);
            if ($categoria) {
                respond(true, $categoria->toArray(), 'Categoría añadida correctamente');
            } else {
                respond(false, [], 'Error al añadir la categoría');
            }
            break;

        // ================================
        // ACTUALIZAR CATEGORÍA
        // ================================
        case 'PUT':
            $id = (int) ($input['id'] ?? 0);
            $nombre = $input['izena'] ?? '';
            if (!$id || !$nombre)
                respond(false, [], 'Faltan campos obligatorios');

            $categoria = Kategoria::getById($id);
            if (!$categoria)
                respond(false, [], 'Categoría no encontrada');

            if ($categoria->update($nombre)) {
                respond(true, $categoria->toArray(), 'Categoría actualizada correctamente');
            } else {
                respond(false, [], 'Error al actualizar la categoría');
            }
            break;

        // ================================
        // ELIMINAR CATEGORÍA
        // ================================
        case 'DELETE':
            $id = (int) ($input['id'] ?? 0);
            if (!$id)
                respond(false, [], 'Falta el ID de la categoría');

            $categoria = Kategoria::getById($id);
            if (!$categoria)
                respond(false, [], 'Categoría no encontrada');

            // 🔹 Verificar si hay equipos asociados
            $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM ekipamendua WHERE idKategoria = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row['cnt'] > 0) {
                respond(false, [], "No se puede eliminar la categoría. Tiene {$row['cnt']} equipos asociados.");
            }

            // 🔹 Si no hay equipos, eliminar
            if ($categoria->delete()) {
                respond(true, [], 'Categoría eliminada correctamente');
            } else {
                respond(false, [], 'Error al eliminar la categoría');
            }
            break;


        default:
            respond(false, [], 'Método no soportado');
    }

} catch (Exception $e) {
    respond(false, [], 'Error: ' . $e->getMessage());
}
