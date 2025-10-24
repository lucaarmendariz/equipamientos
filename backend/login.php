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

    // Buscar el usuario y traer todos los campos necesarios
    $stmt = $mysqli->prepare("SELECT nan, izena, abizena, erabiltzailea, pasahitza, rola FROM erabiltzailea WHERE erabiltzailea = ?");
    if (!$stmt) {
        $response['message'] = 'Error en la base de datos.';
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        // Comparar contraseñas
        if ($password === $user['pasahitza']) {
            session_regenerate_id(true);
            $_SESSION['username'] = $user['erabiltzailea'];
            $_SESSION['rola'] = $user['rola'];

            $response['success'] = true;
            $response['message'] = 'Inicio de sesión correcto. Redirigiendo...';
            $response['redirect'] = '../frontend/menu.html';
            // Enviar todos los datos necesarios para sessionStorage
            $response['rola'] = $user['rola'];
            $response['erabiltzailea'] = $user['erabiltzailea'];
            $response['izena'] = $user['izena'];
            $response['abizena'] = $user['abizena'];
        } else {
            $response['message'] = 'El usuario o la contraseña son incorrectos. Inténtalo de nuevo.';
        }
    } else {
        $response['message'] = 'El usuario o la contraseña son incorrectos. Inténtalo de nuevo.';
    }

    $stmt->close();
    echo json_encode($response);
    exit;
}

$response['message'] = 'Método no permitido.';
echo json_encode($response);
exit;
?>
