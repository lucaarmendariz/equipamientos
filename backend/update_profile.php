<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once 'conexion.php'; // Conexión $mysqli

$response = [
    'success' => false,
    'message' => ''
];

// Comprobar método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos JSON enviados desde frontend
    $input = json_decode(file_get_contents('php://input'), true);

    $username = trim($input['username'] ?? '');
    $name = trim($input['name'] ?? '');
    $lastname = trim($input['lastname'] ?? '');

    if (!$username || !$name || !$lastname) {
        $response['message'] = 'Datos incompletos.';
        echo json_encode($response);
        exit;
    }

    // Comprobar que el usuario logeado coincide con el que intenta modificar
    if (!isset($_SESSION['username']) || $_SESSION['username'] !== $username) {
        $response['message'] = 'No autorizado.';
        echo json_encode($response);
        exit;
    }

    // Actualizar el nombre y apellido en la base de datos
    $stmt = $mysqli->prepare("UPDATE erabiltzailea SET izena = ?, abizena = ? WHERE erabiltzailea = ?");
    if (!$stmt) {
        $response['message'] = 'Error en la base de datos.';
        echo json_encode($response);
        exit;
    }

    $stmt->bind_param('sss', $name, $lastname, $username);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Perfil actualizado correctamente.';
    } else {
        $response['message'] = 'Error al actualizar el perfil.';
    }

    $stmt->close();
    echo json_encode($response);
    exit;
}

// Método no permitido
$response['message'] = 'Método no permitido.';
echo json_encode($response);
exit;
