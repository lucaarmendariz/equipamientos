<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = 'localhost';
$db = 'erronka';
$user = 'root';
$pass = 'admin';

// Crear conexión
$mysqli = new mysqli($host, $user, $pass, $db);

// Comprobar conexión
if ($mysqli->connect_errno) {
    die(json_encode(['success' => false, 'message' => 'Error de conexión: ' . $mysqli->connect_error]));
}

// Establecer conjunto de caracteres a UTF-8
$mysqli->set_charset("utf8");
?>
