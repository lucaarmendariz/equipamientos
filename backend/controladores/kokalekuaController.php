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

                // Obtener nombre del equipo
                $stmt = $conn->prepare("SELECT izena FROM ekipamendua WHERE id = ?");
                $stmt->bind_param("i", $idEkipamendu);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                $nombreEquipo = $result['izena'] ?? '';

                // Obtener todos los kokalekus de este equipo
                $stmt = $conn->prepare("
        SELECT i.etiketa, k.idGela, k.hasieraData, k.amaieraData
        FROM inbentarioa i
        LEFT JOIN kokalekua k ON i.etiketa = k.etiketa
        WHERE i.idEkipamendu = ?
        ORDER BY i.etiketa
    ");
                $stmt->bind_param("i", $idEkipamendu);
                $stmt->execute();
                $kokalekus = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Contar stock disponible (ni hasiera ni amaiera)
                $stockDisponible = count(array_filter($kokalekus, fn($k) => is_null($k['hasieraData']) && is_null($k['amaieraData'])));

                // Contar unidades activas (hasieraData pero sin amaieraData)
                $activos = count(array_filter($kokalekus, fn($k) => !is_null($k['hasieraData']) && is_null($k['amaieraData'])));

                respond(true, [
                    'nombre' => $nombreEquipo,
                    'stockDisponible' => $stockDisponible,
                    'activos' => $activos,
                    'kokalekus' => $kokalekus
                ]);
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
            $hasieraData = $input['hasieraData'] ?? date("Y-m-d");
            $cantidad = (int) ($input['cantidad'] ?? 1);

            if (!$idGela || !$idEkipamendu) {
                respond(false, [], "Faltan campos obligatorios o cantidad inválida");
            }


            $success = Kokaleku::create($idGela, $idEkipamendu, $cantidad, $hasieraData);
            respond($success, [], $success ? "Kokaleku(s) creado(s) correctamente" : "No hay suficientes unidades disponibles para este equipo");
            break;

        // ----------------------------
        // MOVER / REASIGNAR KOKALEKU
        // ----------------------------
        case 'PATCH':
    $etiketa = $input['etiketa'] ?? '';
    $nuevaGela = (int) ($input['nuevaGela'] ?? 0);

    if (!$etiketa || !$nuevaGela) {
        respond(false, [], "Faltan campos obligatorios");
    }

    $conn->begin_transaction();

    try {
        // Obtener kokaleku activo
        $sql = "SELECT idGela, hasieraData FROM kokalekua WHERE etiketa = ? AND amaieraData IS NULL LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare failed (select): " . $conn->error);
        $stmt->bind_param("s", $etiketa);
        $stmt->execute();
        $actual = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$actual) {
            $conn->rollback();
            respond(false, [], "No existe un kokaleku activo para esta etiqueta");
        }

        // Finalizar kokaleku anterior
        $fin = date("Y-m-d H:i:s");
        $sql = "UPDATE kokalekua SET amaieraData = ? WHERE etiketa = ? AND amaieraData IS NULL";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare failed (update): " . $conn->error);
        $stmt->bind_param("ss", $fin, $etiketa);
        $stmt->execute();
        if ($stmt->affected_rows < 1) {
            $stmt->close();
            $conn->rollback();
            respond(false, [], "No se pudo finalizar la asignación (ninguna fila afectada).");
        }
        $stmt->close();

        // Crear nuevo kokaleku con timestamp completo
        $inicio = date("Y-m-d H:i:s");
        $sql = "INSERT INTO kokalekua (etiketa, idGela, hasieraData) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Prepare failed (insert): " . $conn->error);
        $stmt->bind_param("sis", $etiketa, $nuevaGela, $inicio);
        $stmt->execute();
        if ($stmt->affected_rows < 1) {
            throw new Exception("Insert failed: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();

        respond(true, [
            "etiketa" => $etiketa,
            "nuevaGela" => $nuevaGela,
            "hasieraData" => $inicio,
            "amaieraAnterior" => $fin
        ], "Kokaleku movido correctamente");

    } catch (Exception $e) {
        respond(false, [], "No se pudo crear la nueva asignación: " . $e->getMessage());
    }
    break;




        // ----------------------------
        // ACTUALIZAR KOKALEKU
        // ----------------------------
        case 'PUT':
            $etiketa = $input['etiketa'] ?? '';
            $idGela = (int) ($input['idGela'] ?? 0);

            if (!$etiketa || !$idGela) {
                respond(false, [], "Faltan campos obligatorios o cantidad inválida");
            }

            $success = Kokaleku::update($etiketa, $idGela);
            respond($success, [], $success ? "Kokaleku actualizado correctamente" : "No se pudo actualizar el kokaleku");
            break;

        // ----------------------------
        // ELIMINAR KOKALEKU
        // ----------------------------
        case 'DELETE':

            $etiketa = $input['etiketa'] ?? '';

            if (!$etiketa) {
                respond(false, [], "Faltan campos obligatorios");
            }

            // asignar fecha final
            $amaieraData = date("Y-m-d");

            $success = Kokaleku::delete($etiketa, $amaieraData);

            respond($success, [], $success ? "Kokaleku finalizado correctamente" : "Error al finalizar kokaleku");
            break;



        default:
            respond(false, [], "Método no soportado");
    }

} catch (Exception $e) {
    respond(false, [], "Error: " . $e->getMessage());
}
