<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'conexion.php'; // conexión a la base de datos ($mysqli)

$response = [
    'success' => false,
    'message' => '',
    'redirect' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validar campos vacíos
    if ($username === '' || $password === '') {
        $response['message'] = 'Por favor, rellena todos los campos.';
        echo json_encode($response);
        exit;
    }

    // Buscar el usuario
    $stmt = $mysqli->prepare("SELECT erabiltzailea, pasahitza, rola FROM erabiltzailea WHERE erabiltzailea = ?");
    if (!$stmt) {
        $response['message'] = 'Error en la base de datos.';
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Comparar contraseñas (texto plano, ideal usar password_verify)
        if ($password === $user['pasahitza']) {
            session_regenerate_id(true);
            $_SESSION['username'] = $user['erabiltzailea'];
            $_SESSION['rola'] = $user['rola'];

            // ✅ Éxito: el JS se encargará de redirigir
            $response['success'] = true;
            $response['message'] = 'Inicio de sesión correcto. Redirigiendo...';
            $response['redirect'] = (strtolower($user['rola']) === 'a')
                ? '../frontend/admin.html'
                : '../frontend/user.html';
        } else {
            // Contraseña incorrecta
            $response['message'] = 'El usuario o la contraseña son incorrectos. Inténtalo de nuevo.';
        }
    } else {
        // Usuario no encontrado
        $response['message'] = 'El usuario o la contraseña son incorrectos. Inténtalo de nuevo.';
    }

    $stmt->close();
    echo json_encode($response);
    exit;
}

// Si no es método POST
$response['message'] = 'Método no permitido.';
echo json_encode($response);
exit;
?>
