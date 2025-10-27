<?php
declare(strict_types=1);

require_once  '../controladores/conexion.php';

class Kategoria
{
    private int $id;
    private string $izena;

    public function __construct(int $id, string $izena)
    {
        $this->id = $id;
        $this->izena = $izena;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getIzena(): string { return $this->izena; }

    // ---------------------------
    // CRUD
    // ---------------------------

    // Obtener todas las categorías
    public static function getAll(): array
    {
        $conn = DB::getConnection();
        $result = $conn->query("SELECT * FROM kategoria ORDER BY izena");
        $categorias = [];
        while ($row = $result->fetch_assoc()) {
            $categorias[] = new Kategoria((int)$row['id'], $row['izena']);
        }
        return $categorias;
    }

    // Obtener por ID
    public static function getById(int $id): ?Kategoria
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("SELECT * FROM kategoria WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            return new Kategoria((int)$row['id'], $row['izena']);
        }
        return null;
    }

    // Crear nueva categoría
    public static function create(string $izena): ?Kategoria
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("INSERT INTO kategoria (izena) VALUES (?)");
        $stmt->bind_param("s", $izena);
        $success = $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        return $success ? new Kategoria($id, $izena) : null;
    }

    // Actualizar categoría
    public function update(string $nuevoIzena): bool
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("UPDATE kategoria SET izena = ? WHERE id = ?");
        $stmt->bind_param("si", $nuevoIzena, $this->id);
        $success = $stmt->execute();
        if ($success) $this->izena = $nuevoIzena;
        $stmt->close();
        return $success;
    }

    // Eliminar categoría
    public function delete(): bool
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("DELETE FROM kategoria WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // Convertir a array
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'izena' => $this->izena
        ];
    }
}
