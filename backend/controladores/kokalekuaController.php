<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once 'conexion.php';
require_once 'apiKey.php';
require_once '../klaseak/kokalekua.php';
require_once '../klaseak/ekipamenduak.php';

ApiKeyManager::requireApiKey();

function respond(bool $success, array $data = [], string $message = ''): void {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    $conn = DB::getConnection();

    switch ($method) {

        // LISTAR KOKALEKUS o stock de un equipo
        case 'GET':
            $action = $input['action'] ?? 'GET';

            // ----------------------------
            // OBTENER STOCK DE UN EQUIPO
            // ----------------------------
            if (isset($_GET['idEkipamendu'])) {
                $idEkipamendu = (int) $_GET['idEkipamendu'];
                $stmt = $conn->prepare("SELECT COUNT(*) AS stock FROM inbentarioa WHERE idEkipamendu=?");
                $stmt->bind_param("i", $idEkipamendu);
                $stmt->execute();
                $stock = $stmt->get_result()->fetch_assoc()['stock'] ?? 0;
                $stmt->close();
                respond(true, ['stock' => (int)$stock]);
            }

            // ----------------------------
            // LISTAR TODOS LOS KOKALEKUS
            // ----------------------------
            $kokalekus = Kokaleku::getAll();
            respond(true, $kokalekus);
            break;

        // CREAR KOKALEKU(S)
        case 'POST':
            $idGela = (int)($input['idGela'] ?? 0);
            $idEkipamendu = (int)($input['idEkipamendu'] ?? 0);
            $cantidad = (int)($input['cantidad'] ?? 1);
            $hasieraData = $input['hasieraData'] ?? date("Y-m-d");
            $amaieraData = $input['amaieraData'] ?? null;

            if (!$idGela || !$idEkipamendu || $cantidad <= 0) {
                respond(false, [], "Faltan campos obligatorios o cantidad inválida");
            }

            $success = Kokaleku::create($idGela, $idEkipamendu, $cantidad, $hasieraData, $amaieraData);
            if ($success) {
                respond(true, [], "Kokaleku(s) creado(s) correctamente");
            } else {
                respond(false, [], "No hay suficientes unidades disponibles para este equipo");
            }
            break;

        // ELIMINAR KOKALEKU
        case 'DELETE':
            $etiketa = $input['etiketa'] ?? '';
            $hasieraData = $input['hasieraData'] ?? '';
            $amaieraData = $input['amaieraData'] ?? null;

            if (!$etiketa || !$hasieraData) {
                respond(false, [], "Faltan campos obligatorios");
            }

            $success = Kokaleku::delete($etiketa, $hasieraData, $amaieraData);
            respond($success, [], $success ? "Kokaleku eliminado correctamente" : "Error al eliminar kokaleku");
            break;

        default:
            respond(false, [], "Método no soportado");
    }

} catch (Exception $e) {
    respond(false, [], "Error: " . $e->getMessage());
}
