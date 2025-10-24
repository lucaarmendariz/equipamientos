<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'conexion.php'; // conexión $mysqli

$method = $_SERVER['REQUEST_METHOD'];

function respond($success, $data = [], $message = '') {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

switch($method) {
    case 'GET':
        // Obtener lista de equipos con categoría
        $query = "
            SELECT  e.izena, e.deskribapena, e.marka, e.modelo, e.stock, k.izena AS kategoria
            FROM ekipamendua e
            LEFT JOIN kategoria k ON e.idKategoria = k.id
            ORDER BY e.izena
        ";
        $result = $mysqli->query($query);
        if ($result) {
            $data = [];
            while($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            respond(true, $data);
        } else {
            respond(false, [], 'Error en consulta: ' . $mysqli->error);
        }
        break;

    case 'POST':
        // Crear nuevo equipo
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) respond(false, [], 'No se recibieron datos');

        $izena = $mysqli->real_escape_string($input['izena'] ?? '');
        $deskribapena = $mysqli->real_escape_string($input['deskribapena'] ?? '');
        $marka = $mysqli->real_escape_string($input['marka'] ?? '');
        $modelo = $mysqli->real_escape_string($input['modelo'] ?? '');
        $stock = (int)($input['stock'] ?? 0);
        $idKategoria = (int)($input['idKategoria'] ?? 0);

        if (!$izena || !$deskribapena || !$stock || !$idKategoria) {
            respond(false, [], 'Faltan campos obligatorios');
        }

        $sql = "INSERT INTO ekipamendua (izena, deskribapena, marka, modelo, stock, idKategoria)
                VALUES ('$izena', '$deskribapena', '$marka', '$modelo', $stock, $idKategoria)";

        if ($mysqli->query($sql)) {
            respond(true, [], 'Equipo creado correctamente');
        } else {
            respond(false, [], 'Error al insertar: ' . $mysqli->error);
        }
        break;

    case 'PUT':
        // Actualizar equipo
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['id'])) respond(false, [], 'ID requerido para actualizar');

        $id = (int)$input['id'];
        $izena = $mysqli->real_escape_string($input['izena'] ?? '');
        $deskribapena = $mysqli->real_escape_string($input['deskribapena'] ?? '');
        $marka = $mysqli->real_escape_string($input['marka'] ?? '');
        $modelo = $mysqli->real_escape_string($input['modelo'] ?? '');
        $stock = (int)($input['stock'] ?? 0);
        $idKategoria = (int)($input['idKategoria'] ?? 0);

        if (!$izena || !$deskribapena || !$stock || !$idKategoria) {
            respond(false, [], 'Faltan campos obligatorios');
        }

        $sql = "UPDATE ekipamendua SET
                izena='$izena',
                deskribapena='$deskribapena',
                marka='$marka',
                modelo='$modelo',
                stock=$stock,
                idKategoria=$idKategoria
                WHERE id=$id";

        if ($mysqli->query($sql)) {
            respond(true, [], 'Equipo actualizado correctamente');
        } else {
            respond(false, [], 'Error al actualizar: ' . $mysqli->error);
        }
        break;

    case 'DELETE':
        // Eliminar equipo
        parse_str(file_get_contents("php://input"), $del_vars);
        if (empty($del_vars['id'])) respond(false, [], 'ID requerido para eliminar');

        $id = (int)$del_vars['id'];

        $sql = "DELETE FROM ekipamendua WHERE id=$id";

        if ($mysqli->query($sql)) {
            respond(true, [], 'Equipo eliminado correctamente');
        } else {
            respond(false, [], 'Error al eliminar: ' . $mysqli->error);
        }
        break;

    default:
        respond(false, [], 'Método no soportado');
}
