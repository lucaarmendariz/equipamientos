<?php
session_start();
require_once 'conexion.php'; // tu archivo con PDO ($pdo)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        echo "Por favor, rellena todos los campos.";
        exit;
    }

    // Obtenemos la contraseña (texto plano) y el rol
    $stmt = $pdo->prepare("SELECT pasahitza, rola FROM erabiltzailea WHERE erabiltzailea = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Comparación en texto plano (NO segura)
        if ($password === $user['pasahitza']) {
            // Login correcto -> establecer sesión de usuario
            session_regenerate_id(true);
            $_SESSION['username'] = $username;
            $_SESSION['rola'] = $user['rola'];

            if ($user['rola'] === 'a') {
                header("Location: ../frontend/admin.html");
            } else {
                header("Location: ../frontend/user.html");
            }
            exit;
        } else {
            echo "Contraseña incorrecta.";
        }
    } else {
        echo "Usuario no encontrado.";
    }
} else {
    echo "Método no permitido.";
}
?>
