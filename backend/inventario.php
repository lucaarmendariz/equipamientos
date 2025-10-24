<?php
// inventario.php

header('Content-Type: application/json; charset=utf-8');

// Desactivar warnings/notices
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

require_once 'conexion.php'; // conexión $mysqli

// Recibir datos JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? 'list';

$response = [
    'success' => false,
    'data' => [],
    'message' => ''
];

// Captura errores de MySQL como excepciones
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($action) {

        // =================================================
        // LISTAR INVENTARIO
        // =================================================
        case 'list':
            $query = "
                SELECT i.etiketa, e.izena AS equipo, i.erosketaData
                FROM inbentarioa i
                LEFT JOIN ekipamendua e ON i.idEkipamendu = e.id
                ORDER BY i.erosketaData DESC
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
            break;

        // =================================================
        // AÑADIR ITEM AL INVENTARIO
        // =================================================
        case 'add':
            $idEkipamendu = $input['idEkipamendu'] ?? null;
            $fecha = $input['erosketaData'] ?? date('Y-m-d');

            if (!$idEkipamendu) {
                throw new Exception("Falta el ID del equipo");
            }

            // Generar etiqueta automática (E + id + fecha)
            $etiketa = 'I' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

            $stmt = $mysqli->prepare("INSERT INTO inbentarioa (etiketa, idEkipamendu, erosketaData) VALUES (?, ?, ?)");
            $stmt->bind_param("sis", $etiketa, $idEkipamendu, $fecha);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['data'] = [
                'etiketa' => $etiketa,
                'idEkipamendu' => $idEkipamendu,
                'erosketaData' => $fecha
            ];
            break;

        // =================================================
        // ACTUALIZAR ITEM DEL INVENTARIO
        // =================================================
        case 'update':
            $etiketa = $input['etiketa'] ?? null;
            $idEkipamendu = $input['idEkipamendu'] ?? null;
            $fecha = $input['erosketaData'] ?? null;

            if (!$etiketa || !$idEkipamendu || !$fecha) {
                throw new Exception("Faltan campos obligatorios para actualizar");
            }

            $stmt = $mysqli->prepare("UPDATE inbentarioa SET idEkipamendu = ?, erosketaData = ? WHERE etiketa = ?");
            $stmt->bind_param("iss", $idEkipamendu, $fecha, $etiketa);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Inventario actualizado correctamente";
            break;

        // =================================================
        // ELIMINAR ITEM DEL INVENTARIO
        // =================================================
        case 'delete':
            $etiketa = $input['etiketa'] ?? null;
            if (!$etiketa) {
                throw new Exception("Falta la etiqueta para eliminar");
            }

            $stmt = $mysqli->prepare("DELETE FROM inbentarioa WHERE etiketa = ?");
            $stmt->bind_param("s", $etiketa);
            $stmt->execute();
            $stmt->close();

            $response['success'] = true;
            $response['message'] = "Inventario eliminado correctamente";
            break;

        default:
            $response['message'] = "Acción no reconocida";
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Error: " . $e->getMessage();
}

$mysqli->close();
echo json_encode($response);
exit;
