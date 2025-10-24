<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'conexion.php'; // Conexión $mysqli

// Recibir datos JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'list'; // acción por defecto: listar usuarios

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
        case 'list':
            $query = "SELECT nan, izena, abizena, erabiltzailea, rola FROM erabiltzailea";
            $result = $mysqli->query($query);

            if ($result) {
                $usuarios = [];
                while ($row = $result->fetch_assoc()) {
                    $usuarios[] = $row;
                }
                $response['success'] = true;
                $response['data'] = $usuarios;
            } else {
                $response['message'] = "Error en la consulta: " . $mysqli->error;
            }
            break;

        // =================================================
        // ACTUALIZAR PERFIL PROPIO
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

            $stmt = $mysqli->prepare("UPDATE erabiltzailea SET izena = ?, abizena = ? WHERE erabiltzailea = ?");
            if (!$stmt) throw new Exception('Error en la base de datos.');

            $stmt->bind_param('sss', $name, $lastname, $username);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = 'Perfil actualizado correctamente';
            break;

        // =================================================
        // AÑADIR USUARIO
        // =================================================
        case 'POST':
            $username = trim($input['username'] ?? '');
            $name = trim($input['name'] ?? '');
            $lastname = trim($input['lastname'] ?? '');
            $password = trim($input['password'] ?? '');
            $role = trim($input['role'] ?? 'user');

            if (!$username || !$name || !$lastname || !$password) {
                throw new Exception('Datos incompletos para añadir usuario.');
            }

            // Hash de la contraseña
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $mysqli->prepare("INSERT INTO erabiltzailea (izena, abizena, erabiltzailea, password, rola) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $name, $lastname, $username, $hashedPassword, $role);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = 'Usuario añadido correctamente';
            break;

        // =================================================
        // ELIMINAR USUARIO
        // =================================================
        case 'delete':
            $username = trim($input['username'] ?? '');
            if (!$username) throw new Exception('Falta el nombre de usuario para eliminar');

            $stmt = $mysqli->prepare("DELETE FROM erabiltzailea WHERE erabiltzailea = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = 'Usuario eliminado correctamente';
            break;

        default:
            $response['message'] = 'Acción no reconocida';
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Error: " . $e->getMessage();
}

$mysqli->close();
echo json_encode($response);
exit;
