<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once 'conexion.php';
require_once '../klaseak/kokalekua.php';
require_once 'apiKey.php';

ApiKeyManager::requireApiKey(); // ✅ Protección API KEY

function respond(bool $success, array $data = [], string $message = ''): void
{
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    switch ($method) {
        // LISTAR
        case 'GET':
            $kokalekuak = Kokalekua::getAll();
            respond(true, $kokalekuak);
            break;

        // CREAR
        case 'POST':
            $etiketa = $input['etiketa'] ?? '';
            $idGela = (int)($input['idGela'] ?? 0);
            $hasieraData = $input['hasieraData'] ?? date('Y-m-d');

            if (!$etiketa || !$idGela) {
                respond(false, [], 'Faltan datos obligatorios');
            }

            $k = Kokalekua::create($etiketa, $idGela, $hasieraData);
            if ($k)
                respond(true, $k->toArray(), 'Kokalekua sortuta');
            else
                respond(false, [], 'Errorea sortzean');
            break;

        // ELIMINAR
        case 'DELETE':
            $etiketa = $input['etiketa'] ?? '';
            $hasieraData = $input['hasieraData'] ?? '';

            if (!$etiketa || !$hasieraData) {
                respond(false, [], 'Etiketa eta hasieraData beharrezkoak dira');
            }

            $ok = Kokalekua::delete($etiketa, $hasieraData);
            respond($ok, [], $ok ? 'Ezabatuta' : 'Errorea ezabatzean');
            break;

        default:
            respond(false, [], 'Metodoa ez da onartzen');
    }
} catch (Exception $e) {
    respond(false, [], 'Errorea: ' . $e->getMessage());
}
