<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once 'conexion.php';
require_once '../klaseak/ekipamenduak.php';
require_once 'apiKey.php';

ApiKeyManager::requireApiKey(); // <-- aquí se valida la sesión por API key


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
        // LISTAR
        case 'GET':
            $conn = DB::getConnection();

            if (isset($_GET['id']) && is_numeric($_GET['id'])) {
                $id = (int) $_GET['id'];
                $stmt = $conn->prepare("
                    SELECT e.id, e.izena, e.deskribapena, e.marka, e.modelo, e.stock, e.idKategoria, k.izena AS kategoria
                    FROM ekipamendua e
                    LEFT JOIN kategoria k ON e.idKategoria = k.id
                    WHERE e.id = ?
                ");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $equipo = $result->fetch_assoc();
                $stmt->close();

                if ($equipo)
                    respond(true, $equipo);
                else
                    respond(false, [], 'Equipo no encontrado');

            } else {
                $sql = "
                    SELECT e.id, e.izena, e.deskribapena, e.marka, e.modelo, e.stock, e.idKategoria, k.izena AS kategoria
                    FROM ekipamendua e
                    LEFT JOIN kategoria k ON e.idKategoria = k.id
                    ORDER BY e.izena
                ";
                $result = $conn->query($sql);
                $equipos = [];
                while ($row = $result->fetch_assoc()) {
                    $equipos[] = $row;
                }
                respond(true, $equipos);
            }
            break;

        // CREAR
        case 'POST':
            $izena = $input['izena'] ?? '';
            $deskribapena = $input['deskribapena'] ?? '';
            $marka = $input['marca'] ?? null;
            $modelo = $input['modelo'] ?? null;
            $stock = (int) ($input['stock'] ?? 0);
            $idKategoria = (int) ($input['idKategoria'] ?? 0);
            

            if (!$izena || !$deskribapena || !$stock || !$idKategoria) {
                respond(false, [], 'Faltan campos obligatorios');
            }

            $equipo = Ekipamendua::create($izena, $deskribapena, $marka, $modelo, $stock, $idKategoria);
            
            if ($equipo)
                respond(true, $equipo->toArray(), 'Equipo creado correctamente');
            else
                respond(false, [], 'Error al crear equipo');
            break;

        // EDITAR
        case 'PUT':
            // Leer el body JSON
            $rawInput = file_get_contents('php://input');
            $input = json_decode($rawInput, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                respond(false, [], 'JSON inválido en la solicitud PUT');
            }

            // Verificar ID
            $id = isset($input['id']) ? (int) $input['id'] : 0;
            if ($id <= 0)
                respond(false, [], 'ID requerido');

            // Buscar equipo
            $equipo = Ekipamendua::getById($id);
            if (!$equipo)
                respond(false, [], 'Equipo no encontrado');

            // Actualizar campos permitidos
            $campos = ['izena', 'deskribapena', 'marka', 'modelo', 'stock', 'idKategoria'];
            foreach ($campos as $prop) {
                if (array_key_exists($prop, $input)) {
                    if ($prop === 'stock' || $prop === 'idKategoria') {
                        $equipo->$prop = (int) $input[$prop];
                    } else {
                        $equipo->$prop = $input[$prop];
                    }
                }
            }

            // Guardar cambios
            if ($equipo->update()) {
                respond(true, $equipo->toArray(), 'Equipo actualizado correctamente');
            } else {
                respond(false, [], 'Error al actualizar equipo');
            }
            break;
        // ELIMINAR
        case 'DELETE':
            $id = (int) ($input['id'] ?? 0);
            if (!$id)
                respond(false, [], 'ID requerido');

            $equipo = Ekipamendua::getById($id);
            if (!$equipo)
                respond(false, [], 'Equipo no encontrado');

            if ($equipo->delete())
                respond(true, [], 'Equipo eliminado correctamente');
            else
                respond(false, [], 'Error al eliminar equipo');
            break;

        default:
            respond(false, [], 'Método no soportado');
    }

} catch (Exception $e) {
    respond(false, [], 'Error: ' . $e->getMessage());
}
