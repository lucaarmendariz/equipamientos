<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../klaseak/erabiltzailea.php'; // Asegúrate de que la ruta sea correcta

$response = [
    'success' => false,
    'message' => '',
    'redirect' => '',
];

// Solo aceptar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método no permitido.';
    echo json_encode($response);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $response['message'] = 'Faltan campos obligatorios.';
    echo json_encode($response);
    exit;
}

try {
    // Obtener conexión
    $conn = DB::getConnection();
    $stmt = $conn->prepare("SELECT nan, izena, abizena, erabiltzailea, pasahitza, rola 
                            FROM erabiltzailea 
                            WHERE erabiltzailea = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $user = new Erabiltzailea(
            $row['nan'],
            $row['izena'],
            $row['abizena'],
            $row['erabiltzailea'],
            $row['pasahitza'],
            $row['rola']
        );

        // Comparación simple (sin hash)
        $isValid = ($password === $user->getPasahitza());

        if ($isValid) {
            session_regenerate_id(true);
            $_SESSION['username'] = $user->getErabiltzailea();
            $_SESSION['rola'] = $user->getRola();

            $response['success'] = true;
            $response['message'] = 'Inicio de sesión correcto. Redirigiendo...';
            $response['redirect'] = '../frontend/menu.html';
            $response['rola'] = $user->getRola();
            $response['erabiltzailea'] = $user->getErabiltzailea();
            $response['izena'] = $user->getIzena();
            $response['abizena'] = $user->getAbizena();
        } else {
            $response['message'] = 'Usuario o contraseña incorrectos.';
        }
    } else {
        $response['message'] = 'Usuario o contraseña incorrectos.';
    }

    $stmt->close();
} catch (Throwable $e) {
    $response['success'] = false;
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
}

echo json_encode($response);
exit;
