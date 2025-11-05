<?php
declare(strict_types=1);

// Mostrar errores solo en desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once 'conexion.php'; 
require_once '../klaseak/inbentarioa.php'; 

$conn = DB::getConnection();

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['idEkipamendu']) || !isset($data['cantidad'])) {
        throw new Exception('Faltan datos (idEkipamendu, cantidad)');
    }

    $idEkipamendu = intval($data['idEkipamendu']);
    $cantidad = intval($data['cantidad']);
    if ($cantidad <= 0) {
        throw new Exception('Cantidad inválida');
    }

    // Obtener equipo y stock actual
    $stmt = $conn->prepare("SELECT izena, stock FROM ekipamendua WHERE id = ?");
    $stmt->bind_param("i", $idEkipamendu);
    $stmt->execute();
    $result = $stmt->get_result();
    $equipo = $result->fetch_assoc();
    $stmt->close();

    if (!$equipo) {
        throw new Exception("Equipo no encontrado");
    }

    $stock_actual = intval($equipo['stock']);
    $nombre_equipo = $equipo['izena'];

    // ================================
    // INSERTAR EN INVENTARIOA
    // ================================
    $nuevas_etiquetas = [];
    $hoy = date('Y-m-d');

    for ($i = 0; $i < $cantidad; $i++) {
    // Generar una etiqueta única ETK000001 - ETK999999
    // Usamos random_int si está disponible (más seguro), si no, mt_rand.
    $rand = function_exists('random_int') ? random_int(1, 9999) : mt_rand(1, 9999);
    $nuevo_codigo = 'ETK' . sprintf('%04d', $rand); // ETK + 6 dígitos con ceros a la izquierda

    // Comprobación de existencia y reintento si está duplicada
    $check = $conn->prepare("SELECT 1 FROM inbentarioa WHERE etiketa = ?");
    $check->bind_param("s", $nuevo_codigo);
    $check->execute();
    $exists = $check->get_result()->num_rows > 0;
    $check->close();

    if ($exists) {
        $i--; // repetir esta iteración con otro código
        continue;
    }

    // Insertar usando la clase
    $nuevo_item = Inbentarioa::create($nuevo_codigo, $idEkipamendu, $hoy);
    if (!$nuevo_item) {
        throw new Exception("Error al insertar etiqueta $nuevo_codigo: " . $conn->error);
    }

    $nuevas_etiquetas[] = $nuevo_codigo;
}
    // ================================
    // ACTUALIZAR STOCK
    // ================================
    $nuevo_stock = $stock_actual + $cantidad;
    $update = $conn->prepare("UPDATE ekipamendua SET stock = ? WHERE id = ?");
    $update->bind_param("ii", $nuevo_stock, $idEkipamendu);
    $update->execute();
    $update->close();

    // ================================
    // RESPUESTA JSON
    // ================================
    echo json_encode([
        'success' => true,
        'message' => 'Stock actualizado y etiquetas creadas correctamente',
        'nuevas_etiquetas' => $nuevas_etiquetas,
        'nuevo_stock' => $nuevo_stock
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
