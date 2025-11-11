<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once 'conexion.php';
require_once 'apiKey.php';
require_once '../klaseak/kokalekua.php';
require_once '../klaseak/ekipamenduak.php';

ApiKeyManager::requireApiKey();

function respond(bool $success, array $data = [], string $message = ''): void
{
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

try {
    $conn = DB::getConnection();

    switch ($method) {

        // ----------------------------
        // LISTAR / OBTENER KOKALEKU
        // ----------------------------
        case 'GET':
            // Obtener stock de un equipo
            if (isset($_GET['idEkipamendu'])) {
                $idEkipamendu = (int) $_GET['idEkipamendu'];
                $stmt = $conn->prepare("SELECT COUNT(*) AS stock FROM inbentarioa WHERE idEkipamendu=?");
                $stmt->bind_param("i", $idEkipamendu);
                $stmt->execute();
                $stock = $stmt->get_result()->fetch_assoc()['stock'] ?? 0;
                $stmt->close();
                respond(true, ['stock' => (int) $stock]);
            }

            // Obtener kokaleku por etiketa
            if (isset($_GET['etiketa'])) {
                $etiketa = $_GET['etiketa'];
                $kokaleku = Kokaleku::getById($etiketa);
                if ($kokaleku) {
                    respond(true, $kokaleku);
                } else {
                    respond(false, [], "Kokaleku no encontrado");
                }
            }

            // ========================
            // Historial de kokalekus
            // ========================
            if (isset($_GET['historial']) && $_GET['historial'] == "1") {
                $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;
                $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
                $idGela = isset($_GET['idGela']) ? (int) $_GET['idGela'] : null;
                $fechaInicio = $_GET['fechaInicio'] ?? null;
                $fechaFin = $_GET['fechaFin'] ?? null;

                $data = Kokaleku::getAllAmaieraData($idGela, $fechaInicio, $fechaFin, $offset, $limit);
                respond(true, $data);
            }


            // Listar todos los kokalekus activos (sin amaieraData)
            $kokalekus = Kokaleku::getAll();
            respond(true, $kokalekus);
            break;


        // ----------------------------
        // CREAR KOKALEKU(S)
        // ----------------------------
        case 'POST':
            $idGela = (int) ($input['idGela'] ?? 0);
            $idEkipamendu = (int) ($input['idEkipamendu'] ?? 0);
            $cantidad = (int) ($input['cantidad'] ?? 1);
            $hasieraData = $input['hasieraData'] ?? date("Y-m-d");
            $amaieraData = $input['amaieraData'] ?? null;

            if (!$idGela || !$idEkipamendu || $cantidad <= 0) {
                respond(false, [], "Faltan campos obligatorios o cantidad inválida");
            }

            $success = Kokaleku::create($idGela, $idEkipamendu, $cantidad, $hasieraData, $amaieraData);
            respond($success, [], $success ? "Kokaleku(s) creado(s) correctamente" : "No hay suficientes unidades disponibles para este equipo");
            break;

        // ----------------------------
        // ACTUALIZAR KOKALEKU
        // ----------------------------
        case 'PUT':
            $etiketa = $input['etiketa'] ?? '';
            $idGela = (int) ($input['idGela'] ?? 0);
            $hasieraData = $input['hasieraData'] ?? date("Y-m-d");
            $amaieraData = $input['amaieraData'] ?? null;

            if (!$etiketa || !$idGela) {
                respond(false, [], "Faltan campos obligatorios o cantidad inválida");
            }

            $success = Kokaleku::update($etiketa, $idGela, $hasieraData, $amaieraData);
            respond($success, [], $success ? "Kokaleku actualizado correctamente" : "No se pudo actualizar el kokaleku");
            break;

        // ----------------------------
        // ELIMINAR KOKALEKU
        // ----------------------------
        case 'DELETE':
    $etiketa = $input['etiketa'] ?? '';
    $hasieraData = $input['hasieraData'] ?? '';

    if (!$etiketa) {
        respond(false, [], "Faltan campos obligatorios");
    }

    // Asigna automáticamente la fecha actual como fecha de finalización
    $amaieraData = date("Y-m-d"); // con hora exacta del momento de ejecución

    $success = Kokaleku::delete($etiketa, $hasieraData, $amaieraData);
    respond($success, [], $success ? "Kokaleku finalizado correctamente" : "Error al finalizar kokaleku");
    break;


        default:
            respond(false, [], "Método no soportado");
    }

} catch (Exception $e) {
    respond(false, [], "Error: " . $e->getMessage());
}
