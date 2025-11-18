<?php
declare(strict_types=1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../klaseak/erabiltzailea.php';
require_once 'apiKey.php';

ApiKeyManager::requireApiKey(); // <-- aquí se valida la sesión por API key

$input = json_decode(file_get_contents('php://input'), true);
$action = strtoupper(trim($input['action'] ?? 'GET'));

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

try {
    switch ($action) {

        // =================================================
        // LISTAR USUARIOS
        // =================================================
        case 'GET':
            $usuarios = [];
            foreach (Erabiltzailea::getAll() as $user) {
                $usuarios[] = [
                    'nan' => $user->getNan(),
                    'izena' => $user->getIzena(),
                    'abizena' => $user->getAbizena(),
                    'erabiltzailea' => $user->getErabiltzailea(),
                    'rola' => $user->getRola()
                ];
            }
            $response['success'] = true;
            $response['data'] = $usuarios;
            break;

        // =================================================
        // ACTUALIZAR PERFIL (MODIFICAR PROPIO)
        // =================================================
        // =================================================
// ACTUALIZAR PERFIL (MODIFICAR PROPIO) usando API Key
// =================================================
        case 'PUT':
            $username = trim($input['username'] ?? '');
            $name = trim($input['name'] ?? '');
            $lastname = trim($input['lastname'] ?? '');

            if (!$username || !$name || !$lastname) {
                throw new Exception('Datos incompletos.');
            }

            // Obtener username real desde la API Key
            $conn = DB::getConnection();
            $stmt = $conn->prepare("SELECT erabiltzailea FROM erabiltzailea WHERE apiKey = ?");
            $apiKeyFromHeader = null;
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                if (isset($headers['Authorization']) && preg_match('/Bearer\s+(\S+)/', $headers['Authorization'], $matches)) {
                    $apiKeyFromHeader = $matches[1];
                }
            }
            if (!$apiKeyFromHeader && isset($_SERVER['HTTP_AUTHORIZATION'])) {
                if (preg_match('/Bearer\s+(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
                    $apiKeyFromHeader = $matches[1];
                }
            }

            $stmt->bind_param("s", $apiKeyFromHeader);
            $stmt->execute();
            $stmt->bind_result($realUsername);
            if (!$stmt->fetch()) {
                throw new Exception('No autorizado: API Key no válida.');
            }
            $stmt->close();

            // Validar que el usuario del input coincide con la API Key
            if ($realUsername !== $username) {
                throw new Exception('No autorizado: usuario incorrecto.');
            }

            // Actualizar perfil
            $stmt = $conn->prepare("UPDATE erabiltzailea SET izena = ?, abizena = ? WHERE erabiltzailea = ?");
            if (!$stmt)
                throw new Exception('Error en la base de datos.');
            $stmt->bind_param('sss', $name, $lastname, $username);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = 'Perfil actualizado correctamente.';
            break;


        // =================================================
        // ACTUALIZAR USUARIO (ADMIN)
        // =================================================
        case 'UPDATE_ADMIN':
            $nan = trim($input['nan'] ?? '');
            $name = trim($input['name'] ?? '');
            $lastname = trim($input['lastname'] ?? '');
            $username = trim($input['username'] ?? '');
            $password = trim($input['password'] ?? '');
            $role = trim($input['role'] ?? 'U');

            if (!$nan || !$name || !$lastname || !$username) {
                throw new Exception('Datos incompletos para actualizar el usuario.');
            }

            $conn = DB::getConnection();

            // Si se proporciona una contraseña nueva, la actualiza; si no, la deja igual
            if ($password) {
                $stmt = $conn->prepare("UPDATE erabiltzailea SET nan = ?, izena = ?, abizena = ?, pasahitza = ?, rola = ? WHERE erabiltzailea = ?");
                if (!$stmt)
                    throw new Exception('Error preparando la consulta.');
                $stmt->bind_param('ssssss', $nan, $name, $lastname, $password, $role, $username);
            } else {
                $stmt = $conn->prepare("UPDATE erabiltzailea SET nan = ?, izena = ?, abizena = ?, rola = ? WHERE erabiltzailea = ?");
                if (!$stmt)
                    throw new Exception('Error preparando la consulta.');
                $stmt->bind_param('sssss', $nan, $name, $lastname, $role, $username);
            }

            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = 'Usuario actualizado correctamente por administrador.';
            break;

        // =================================================
        // CREAR NUEVO USUARIO
        // =================================================
        case 'POST':
            $nan = trim($input['nan'] ?? '');
            $name = trim($input['name'] ?? '');
            $lastname = trim($input['lastname'] ?? '');
            $username = trim($input['username'] ?? '');
            $password = trim($input['password'] ?? '');
            $role = trim($input['role'] ?? 'U');

            if (!$nan || !$name || !$lastname || !$username || !$password) {
                throw new Exception('Datos incompletos para crear usuario.');
            }

            // Usa el método estático de la clase
            $user = Erabiltzailea::create($nan, $name, $lastname, $username, $password, $role);

            if ($user) {
                $response['success'] = true;
                $response['message'] = 'Usuario creado correctamente.';
            } else {
                throw new Exception('Error al crear el usuario.');
            }
            break;

        // =================================================
        // ELIMINAR USUARIO POR NAN
        // =================================================
        case 'DELETE':
            $nan = trim($input['nan'] ?? '');
            if (!$nan) {
                throw new Exception('Falta el NAN del usuario.');
            }

            $conn = DB::getConnection();
            $stmt = $conn->prepare("DELETE FROM erabiltzailea WHERE nan = ?");
            if (!$stmt) {
                throw new Exception('Error al preparar la consulta.');
            }

            $stmt->bind_param("s", $nan);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = 'Usuario eliminado correctamente.';
            break;

        default:
            throw new Exception('Acción no reconocida.');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($response);
exit;
