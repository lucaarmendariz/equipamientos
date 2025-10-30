<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);

require_once 'conexion.php';
require_once '../klases/Kategoria.php';

// Función para enviar respuesta JSON
function respond($success, $data = [], $message = '') {
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// Obtener el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch($method) {

        // ====================== OBTENER TODAS LAS CATEGORÍAS ======================
        case 'GET':
            $categorias = Kategoria::obtenerTodos($mysqli);
            respond(true, $categorias);
            break;

        // ====================== CREAR NUEVA CATEGORÍA ======================
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['izena']) || trim($input['izena']) === '') {
                respond(false, [], 'Nombre de categoría requerido');
            }

            $nombre = trim($input['izena']);
            $cat = new Kategoria(null, $nombre, $mysqli);
            $cat->guardar();

            respond(true, ['id' => $cat->id], 'Categoría creada correctamente');
            break;

        // ====================== ACTUALIZAR CATEGORÍA ======================
        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['id']) || !isset($input['izena']) || trim($input['izena']) === '') {
                respond(false, [], 'ID y nombre requeridos');
            }

            $id = (int)$input['id'];
            $nombre = trim($input['izena']);

            $cat = Kategoria::buscarPorId($id, $mysqli);
            if (!$cat) respond(false, [], 'Categoría no encontrada');

            $cat->nombre = $nombre;
            $cat->actualizar();
            respond(true, [], 'Categoría actualizada correctamente');
            break;

        // ====================== ELIMINAR CATEGORÍA ======================
        case 'DELETE':
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input || !isset($input['id'])) respond(false, [], 'ID requerido para eliminar');

            $id = (int)$input['id'];
            $cat = Kategoria::buscarPorId($id, $mysqli);
            if (!$cat) respond(false, [], 'Categoría no encontrada');

            $cat->eliminar();
            respond(true, [], 'Categoría eliminada correctamente');
            break;

        default:
            respond(false, [], 'Método no soportado');
    }

} catch(Exception $e) {
    respond(false, [], 'Error en servidor: ' . $e->getMessage());
}
