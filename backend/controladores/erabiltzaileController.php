<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../klaseak/erabiltzailea.php';

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
        case 'PUT':
            $username = trim($input['username'] ?? '');
            $name = trim($input['name'] ?? '');
            $lastname = trim($input['lastname'] ?? '');

            if (!$username || !$name || !$lastname) {
                throw new Exception('Datos incompletos.');
            }

            if (!isset($_SESSION['username']) || $_SESSION['username'] !== $username) {
                throw new Exception('No autorizado.');
            }

            $conn = DB::getConnection();
            $stmt = $conn->prepare("UPDATE erabiltzailea SET izena = ?, abizena = ? WHERE erabiltzailea = ?");
            if (!$stmt) throw new Exception('Error en la base de datos.');

            $stmt->bind_param('sss', $name, $lastname, $username);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = 'Perfil actualizado correctamente.';
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
        // ELIMINAR USUARIO
        // =================================================
        case 'DELETE':
            $username = trim($input['username'] ?? '');
            if (!$username) throw new Exception('Falta el nombre de usuario.');

            $conn = DB::getConnection();
            $stmt = $conn->prepare("DELETE FROM erabiltzailea WHERE erabiltzailea = ?");
            $stmt->bind_param("s", $username);
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
