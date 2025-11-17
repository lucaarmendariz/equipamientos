<?php
declare(strict_types=1);
session_start();
header(header: 'Content-Type: application/json; charset=utf-8');

require_once '../klaseak/erabiltzailea.php';
require_once 'apiKey.php'; // añadimos el sistema de API keys



$response = [
    'success' => false,
    'message' => '',
    'redirect' => '',
];

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
        if ($password === $user->getPasahitza()) {

            // ✅ Generar o asignar API key al usuario
            $apiKey = ApiKeyManager::assignApiKey($user->getErabiltzailea(), false);

            $response = [
                'success' => true,
                'message' => 'Inicio de sesión correcto.',
                'redirect' => '../frontend/menu.html',
                'rola' => $user->getRola(),
                'erabiltzailea' => $user->getErabiltzailea(),
                'izena' => $user->getIzena(),
                'abizena' => $user->getAbizena(),
                'apiKey' => $apiKey // devolvemos la clave
            ];
        } else {
            $response['message'] = 'Usuario o contraseña incorrectos.';
        }
    } else {
        $response['message'] = 'Usuario o contraseña incorrectos.';
    }

    $stmt->close();
} catch (Throwable $e) {
    $response['message'] = 'Error del servidor: ' . $e->getMessage();
}

echo json_encode($response);
exit;
