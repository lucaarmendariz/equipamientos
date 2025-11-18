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

require_once 'conexion.php';
require_once '../klaseak/Gela.php';
require_once 'apiKey.php';

ApiKeyManager::requireApiKey(); // Validación API key

// Función para enviar JSON limpio
function respond(bool $success, array $data = [], string $message = ''): void
{
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($method) {

        // LISTAR GELAS
        case 'GET':
            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $id = (int) $_GET['id'];
                $gela = Gela::getById($id);
                if ($gela) {
                    respond(true, $gela->toArray());
                } else {
                    respond(false, [], 'Gela no encontrada');
                }
            } else {
                $allGelak = Gela::getAll();
                $gelak = array_map(fn($g) => $g->toArray(), $allGelak);
                respond(true, $gelak);
            }
            break;

        // CREAR GELA
        case 'POST':
            $izena = $input['izena'] ?? null;
            $taldea = $input['taldea'] ?? null;

            if (!$izena) {
                respond(false, [], 'Falta el nombre de la gela');
            }

            $gela = Gela::create($izena, $taldea);
            if ($gela) {
                respond(true, $gela->toArray(), 'Gela creada correctamente');
            } else {
                respond(false, [], 'Error al crear la gela');
            }
            break;

        // ACTUALIZAR GELA
        case 'PUT':
            $id = isset($input['id']) ? (int) $input['id'] : null;
            $izena = $input['izena'] ?? null;
            $taldea = $input['taldea'] ?? null;

            if (!$id || !$izena) {
                respond(false, [], 'Faltan campos obligatorios');
            }

            $gela = Gela::getById($id);
            if (!$gela) {
                respond(false, [], 'Gela no encontrada');
            }

            // Actualizar propiedades directamente
            $gela->izena = $izena;
            $gela->taldea = $taldea;

            // Guardar cambios
            $stmt = DB::getConnection()->prepare("UPDATE gela SET izena = ?, taldea = ? WHERE id = ?");
            $stmt->bind_param("ssi", $gela->izena, $gela->taldea, $gela->id);
            $success = $stmt->execute();
            $stmt->close();

            if ($success) {
                respond(true, $gela->toArray(), 'Gela actualizada correctamente');
            } else {
                respond(false, [], 'Error al actualizar la gela');
            }
            break;

        // ELIMINAR GELA
        case 'DELETE':
            $id = isset($input['id']) ? (int) $input['id'] : null;
            if (!$id) {
                respond(false, [], 'Falta el ID de la gela');
            }

            $gela = Gela::getById($id);
            if (!$gela) {
                respond(false, [], 'Gela no encontrada');
            }

            if ($gela->delete()) {
                respond(true, [], 'Gela eliminada correctamente');
            } else {
                respond(false, [], 'Error al eliminar la gela');
            }
            break;

        default:
            respond(false, [], 'Método no soportado');
    }

} catch (Exception $e) {
    respond(false, [], 'Error: ' . $e->getMessage());
}
