<?php
$host = 'localhost';
$db = 'erronka';
$user = 'root';
$pass = 'ikasle123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Kaixo konexio ona";
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
