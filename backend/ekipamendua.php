<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

require_once 'conexion.php';
require_once '../klases/Ekipamendua.php';

function respond($success, $data = [], $message = '') {
    echo json_encode(['success'=>$success,'data'=>$data,'message'=>$message]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$equipo = new Ekipamendua($mysqli);

try {
    switch($method) {
        case 'GET':
            // Obtener todos los equipos
            $equipo = new Ekipamendua($mysqli);
            $equipos = $equipo->getAll();
            respond(true, $equipos);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) respond(false, [], 'Datos no recibidos');

            $id = $equipo->create($input);
            respond(true, ['id'=>$id], 'Equipo creado correctamente');
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['id'])) respond(false, [], 'ID requerido para actualizar');

            $id = (int)$input['id'];
            $existing = $equipo->findById($id);
            if (!$existing) respond(false, [], 'Equipo no encontrado');

            $equipo->update($id, $input);
            respond(true, [], 'Equipo actualizado correctamente');
            break;

        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || empty($input['id'])) respond(false, [], 'ID requerido para eliminar');

            $id = (int)$input['id'];
            $existing = $equipo->findById($id);
            if (!$existing) respond(false, [], 'Equipo no encontrado');

            $equipo->delete($id);
            respond(true, [], 'Equipo eliminado correctamente');
            break;

        default:
            respond(false, [], 'Método no soportado');
    }
} catch(Exception $e) {
    respond(false, [], 'Error en servidor: '.$e->getMessage());
}
?>
