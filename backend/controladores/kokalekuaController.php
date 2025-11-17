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

            if (!$idGela || !$idEkipamendu || $cantidad < 0) {
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

            // 1️⃣ Obtener el kokaleku activo actual
            $stmt = $conn->prepare("
                SELECT idEkipamendu, idGela 
                FROM kokalekua 
                WHERE etiketa = ? AND amaieraData IS NULL
                LIMIT 1
            ");
            $stmt->bind_param("s", $etiketa);
            $stmt->execute();
            $actual = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$actual) {
                respond(false, [], "No existe un kokaleku activo para esta etiqueta");
            }

            $idEkipamendu = (int) $actual['idEkipamendu'];

            // 2️⃣ Finalizar el kokaleku anterior (asignar amaieraData)
            $fin = date("Y-m-d");

            $stmt = $conn->prepare("
                UPDATE kokalekua
                SET amaieraData = ?
                WHERE etiketa = ? AND amaieraData IS NULL
            ");
            $stmt->bind_param("ss", $fin, $etiketa);
            $stmt->execute();
            $stmt->close();

            // 3️⃣ Crear un nuevo kokaleku con la misma etiqueta
            $inicio = date("Y-m-d");

            $stmt = $conn->prepare("
                INSERT INTO kokalekua (etiketa, idGela, idEkipamendu, hasieraData, amaieraData)
                VALUES (?, ?, ?, ?, NULL)
            ");
            $stmt->bind_param("siis", $etiketa, $nuevaGela, $idEkipamendu, $inicio);
            $stmt->execute();
            $stmt->close();

            respond(true, [
                "etiketa" => $etiketa,
                "nuevaGela" => $nuevaGela,
                "idEkipamendu" => $idEkipamendu,
                "hasieraData" => $inicio,
                "amaieraAnterior" => $fin
            ], "Kokaleku movido correctamente");

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
