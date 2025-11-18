<?php
declare(strict_types=1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

class DB
{
    private static ?mysqli $conn = null;

    public static function getConnection(): mysqli
    {
        if (self::$conn === null) {
            $host = 'localhost';
            $user = 'root';
            $pass = 'ikasle123';
            $db = 'erronkae1t2';

            self::$conn = new mysqli($host, $user, $pass, $db);
            if (self::$conn->connect_errno) {
                throw new Exception('Error de conexión: ' . self::$conn->connect_error);
            }
            self::$conn->set_charset('utf8');
        }

        return self::$conn;
    }
}
