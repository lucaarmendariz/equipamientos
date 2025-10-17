<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php';

// Recibir datos JSON
$input = json_decode(file_get_contents('php://input'), true);

// Validar campos obligatorios
$nombre      = $input['nombre'] ?? null;
$descripcion = $input['descripcion'] ?? null;
$marca       = $input['marca'] ?? '';
$modelo      = $input['modelo'] ?? '';
$stock       = isset($input['stock']) ? (int)$input['stock'] : 0;
$idCategoria = isset($input['idCategoria']) ? (int)$input['idCategoria'] : null;
$idGela      = isset($input['idGela']) ? (int)$input['idGela'] : null;

if (!$nombre || !$descripcion || !$idCategoria || !$idGela) {
    echo json_encode(["success" => false, "message" => "Faltan campos obligatorios"]);
    exit;
}

try {
    // Iniciar transacción
    $mysqli->begin_transaction();

    // Insertar equipo
    $stmt = $mysqli->prepare("INSERT INTO ekipamendua (izena, deskribapena, marka, modelo, stock, idKategoria) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiii", $nombre, $descripcion, $marca, $modelo, $stock, $idCategoria);
    $stmt->execute();
    $idEquipo = $stmt->insert_id;
    $stmt->close();

    // Crear etiqueta para inventario
    $etiketa = 'E' . str_pad($idEquipo, 6, '0', STR_PAD_LEFT);
    $fechaHoy = date('Y-m-d');

    // Insertar en inbentarioa
    $stmtInv = $mysqli->prepare("INSERT INTO inbentarioa (etiketa, idEkipamendu, erosketaData) VALUES (?, ?, ?)");
    $stmtInv->bind_param("sis", $etiketa, $idEquipo, $fechaHoy);
    $stmtInv->execute();
    $stmtInv->close();

    // Insertar en kokalekua
    $stmtKok = $mysqli->prepare("INSERT INTO kokalekua (etiketa, idGela, hasieraData) VALUES (?, ?, ?)");
    $stmtKok->bind_param("sis", $etiketa, $idGela, $fechaHoy);
    $stmtKok->execute();
    $stmtKok->close();

    // Confirmar transacción
    $mysqli->commit();

    echo json_encode([
        "success"      => true,
        "id"           => $idEquipo,
        "nombre"       => $nombre,
        "descripcion"  => $descripcion,
        "stock"        => $stock,
        "idCategoria"  => $idCategoria,
        "idGela"       => $idGela,
        "etiketa"      => $etiketa
    ]);

} catch (Exception $e) {
    $mysqli->rollback(); // Revertir todo si hay error
    echo json_encode(["success" => false, "message" => "Error al insertar el equipo: " . $e->getMessage()]);
}

$mysqli->close();
