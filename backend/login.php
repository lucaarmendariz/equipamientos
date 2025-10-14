<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'conexion.php';

$response = [
    'success' => false,
    'user' => null,
    'redirect' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $mysqli->prepare("SELECT pasahitza, rola FROM erabiltzailea WHERE erabiltzailea = ?");
    if (!$stmt) {
        $response['message'] = 'Error en la base de datos.';
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if ($password === $user['pasahitza']) { // o password_verify si está en hash
            session_regenerate_id(true);
            $_SESSION['username'] = $username;
            $_SESSION['rola'] = $user['rola'];

            $response['success'] = true;
            $response['user'] = ['username' => $username, 'role' => $user['rola']];

            // Redirigir según rol (solo en JS)
            $response['redirect'] = strtolower($user['rola']) === 'a' ? '../frontend/admin.html' : '../frontend/user.html';
        } 
    }

    $stmt->close();
    echo json_encode($response);
} else {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
}
