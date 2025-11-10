<?php
declare(strict_types=1);
header("Content-Type: application/json");
require_once "../klaseak/gestion.php";

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $gestioak = Gestioa::getAll();
        echo json_encode([
            "success" => true,
            "data" => array_map(fn($g) => $g->toArray(), $gestioak)
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Metodo ez onartua"
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}