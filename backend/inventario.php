<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';
require_once '../klases/Inbentarioa.php';

function respond($success, $data = [], $message = '') {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

$inventario = new Inbentarioa($mysqli);

try {
    $data = $inventario->getAll();
    respond(true, $data);
} catch (Exception $e) {
    respond(false, [], $e->getMessage());
}
