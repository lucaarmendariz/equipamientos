<?php
// inventario.php

header('Content-Type: application/json; charset=utf-8');

// Desactivar warnings/notices
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

require_once 'conexion.php'; // conexión $mysqli

function respond($success, $data = [], $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

// Captura errores de MySQL como excepciones
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$method = $_SERVER['REQUEST_METHOD'];

try {

    switch ($method) {

        // ====================== OBTENER INVENTARIO ======================
        case 'GET':
            $result = $mysqli->query("
                SELECT i.etiketa, i.idEkipamendu, e.izena AS equipo, i.erosketaData
                FROM inbentarioa i
                LEFT JOIN ekipamendua e ON i.idEkipamendu = e.id
                ORDER BY i.etiketa DESC
            ");
            $data = $result->fetch_all(MYSQLI_ASSOC);
            respond(true, $data);
            break;

        // ====================== CREAR / ELIMINAR ======================
        case 'POST':
            $action = $_POST['action'] ?? '';

            // -------- ELIMINAR INVENTARIO CON DEPENDENCIAS --------
            if ($action === 'delete') {
                $etiketa = $mysqli->real_escape_string($_POST['etiketa'] ?? '');
                if (!$etiketa) respond(false, [], 'Etiqueta requerida');

                try {
                    // Borrar registros dependientes en kokalekua
                    $mysqli->query("DELETE FROM kokalekua WHERE etiketa='$etiketa'");
                    // Borrar inventario
                    $mysqli->query("DELETE FROM inbentarioa WHERE etiketa='$etiketa'");
                    respond(true, [], 'Inventario y dependencias eliminadas correctamente');
                } catch (mysqli_sql_exception $e) {
                    respond(false, [], 'No se pudo eliminar: ' . $e->getMessage());
                }
            }

            // -------- CREAR INVENTARIO --------
            $etiketa = $mysqli->real_escape_string($_POST['etiketa'] ?? '');
            $idEkipamendu = (int)($_POST['idEkipamendu'] ?? 0);
            $erosketaData = $mysqli->real_escape_string($_POST['erosketaData'] ?? '');

            if (!$etiketa || !$idEkipamendu || !$erosketaData) {
                respond(false, [], 'Campos obligatorios faltan');
            }

            $exists = $mysqli->query("SELECT etiketa FROM inbentarioa WHERE etiketa='$etiketa'")->num_rows;
            if ($exists) respond(false, [], 'La etiqueta ya existe');

            $mysqli->query("INSERT INTO inbentarioa (etiketa, idEkipamendu, erosketaData)
                            VALUES ('$etiketa', $idEkipamendu, '$erosketaData')");
            respond(true, [], 'Inventario creado correctamente');
            break;

        // ====================== ACTUALIZAR INVENTARIO ======================
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['etiketa'])) respond(false, [], 'Etiqueta requerida para actualizar');

            $etiketa = $mysqli->real_escape_string($input['etiketa']);
            $idEkipamendu = (int)($input['idEkipamendu'] ?? 0);
            $erosketaData = $mysqli->real_escape_string($input['erosketaData'] ?? '');

            if (!$idEkipamendu || !$erosketaData) respond(false, [], 'Campos obligatorios faltan');

            $mysqli->query("UPDATE inbentarioa 
                            SET idEkipamendu=$idEkipamendu, erosketaData='$erosketaData' 
                            WHERE etiketa='$etiketa'");
            respond(true, [], 'Inventario actualizado correctamente');
            break;

        // ====================== MÉTODO NO SOPORTADO ======================
        default:
            respond(false, [], 'Método no soportado');
    }

} catch (Exception $e) {
    respond(false, [], 'Error en servidor: ' . $e->getMessage());
}