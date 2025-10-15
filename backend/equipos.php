<?php
// equipamientos.php
header('Content-Type: application/json; charset=utf-8');

require_once 'conexion.php'; // tu archivo de conexión $mysqli

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

try {
    // Consulta simple para obtener algunos campos de equipamientos
    $query = "SELECT id, izena, stock, marka, modelo FROM ekipamendua LIMIT 20";
    $result = $mysqli->query($query);

    if ($result) {
        $equipamientos = [];
        while ($row = $result->fetch_assoc()) {
            $equipamientos[] = $row;
        }

        $response['success'] = true;
        $response['data'] = $equipamientos;
    } else {
        $response['message'] = "Error en la consulta: " . $mysqli->error;
    }

} catch (Exception $e) {
    $response['message'] = "Excepción: " . $e->getMessage();
}

// Retornar JSON limpio
echo json_encode($response);
exit;
