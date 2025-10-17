<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$result = $mysqli->query("SELECT id, izena FROM kategoria ORDER BY izena ASC");

$categorias = [];
while ($row = $result->fetch_assoc()) {
    $categorias[] = $row;
}

echo json_encode($categorias);

$mysqli->close();
