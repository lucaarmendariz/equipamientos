<?php
require_once "conexion.php";

class ApiKeyManager
{
    // Comprueba y crea la columna apiKey si no existe
    public static function ensureApiKeyColumn()
    {
        $conn = DB::getConnection();
        $result = $conn->query("SHOW COLUMNS FROM erabiltzailea LIKE 'apiKey'");
        if ($result->num_rows === 0) {
            $conn->query("ALTER TABLE erabiltzailea ADD COLUMN apiKey VARCHAR(64) DEFAULT NULL");
        }
        $result->close();
    }

    // Genera una apiKey segura
    public static function generateApiKey(): string
    {
        return bin2hex(random_bytes(32)); // 64 caracteres hexadecimales
    }

    // Asigna una apiKey a un usuario (por su NAN o username)
    public static function assignApiKey($identifier, $byNan = true)
    {
        $conn = DB::getConnection();
        $apiKey = self::generateApiKey();
        $field = $byNan ? "nan" : "erabiltzailea";

        $stmt = $conn->prepare("UPDATE erabiltzailea SET apiKey = ? WHERE $field = ?");
        $stmt->bind_param("ss", $apiKey, $identifier);
        $stmt->execute();
        $stmt->close();

        return $apiKey;
    }

    // Valida una apiKey (devuelve true si existe)
    public static function validateApiKey($apiKey): bool
    {
        if (!$apiKey) return false;

        $conn = DB::getConnection();
        $stmt = $conn->prepare("SELECT nan FROM erabiltzailea WHERE apiKey = ?");
        $stmt->bind_param("s", $apiKey);
        $stmt->execute();
        $stmt->store_result();
        $valid = $stmt->num_rows > 0;
        $stmt->close();
        return $valid;
    }

    // Middleware simple: fuerza que haya una apiKey válida
    public static function requireApiKey()
{
    self::ensureApiKeyColumn(); // Asegura que la columna existe

    $apiKey = null;

    // 1️⃣ — Leer todas las cabeceras HTTP
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            // Formato: "Bearer XXXXX..."
            if (preg_match('/Bearer\s+(\S+)/', $headers['Authorization'], $matches)) {
                $apiKey = $matches[1];
            }
        } elseif (isset($headers['ApiKey'])) {
            // Alternativamente: ApiKey: XXXXX
            $apiKey = trim($headers['ApiKey']);
        }
    }

    // 2️⃣ — Alternativas: variables del servidor
    if (!$apiKey && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $apiKey = $matches[1];
        }
    }

    if (!$apiKey && isset($_SERVER['HTTP_APIKEY'])) {
        $apiKey = trim($_SERVER['HTTP_APIKEY']);
    }

    // 3️⃣ — Alternativas por GET o POST
    if (!$apiKey) {
        $apiKey = $_GET['apiKey'] ?? $_POST['apiKey'] ?? null;
    }

    // 4️⃣ — Validar API key
    if (!self::validateApiKey($apiKey)) {
        http_response_code(401);
        echo json_encode([
            "success" => false,
            "message" => "API key inválida o no proporcionada."
        ]);
        exit;
    }
}

}

// Se asegura siempre que la columna exista
ApiKeyManager::ensureApiKeyColumn();
