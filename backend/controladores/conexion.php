<?php
declare(strict_types=1);

class DB
{
    private static ?mysqli $conn = null;

    public static function getConnection(): mysqli
    {
        if (self::$conn === null) {
            $host = 'localhost';
            $user = 'root';
            $pass = '';
            $db = 'erronkabd';

            self::$conn = new mysqli($host, $user, $pass, $db);
            if (self::$conn->connect_errno) {
                throw new Exception('Error de conexión: ' . self::$conn->connect_error);
            }
            self::$conn->set_charset('utf8');
        }

        return self::$conn;
    }
}
