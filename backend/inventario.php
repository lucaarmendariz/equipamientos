<?php
// inventario.php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php'; // conexión $mysqli

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

try {
    // Hacemos un JOIN para traer el nombre del equipo
    $query = "
        SELECT i.etiketa, e.izena AS equipo, i.erosketaData
        FROM inbentarioa i
        LEFT JOIN ekipamendua e ON i.idEkipamendu = e.id
        LIMIT 20
    ";
    
    $result = $mysqli->query($query);

    if ($result) {
        $inventario = [];
        while ($row = $result->fetch_assoc()) {
            $inventario[] = $row;
        }

        $response['success'] = true;
        $response['data'] = $inventario;
    } else {
        $response['message'] = "Error en la consulta: " . $mysqli->error;
    }

} catch (Exception $e) {
    $response['message'] = "Excepción: " . $e->getMessage();
}

echo json_encode($response);
exit;
