<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

$result = $mysqli->query("SELECT id, izena FROM gela ORDER BY izena ASC");

$gelas = [];
while ($row = $result->fetch_assoc()) {
    $gelas[] = $row;
}

echo json_encode($gelas);

$mysqli->close();
