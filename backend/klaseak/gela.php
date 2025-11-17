<?php
declare(strict_types=1);
require_once "../controladores/conexion.php"; // aquí está la clase DB

class Gela
{
    public int $id;
    public string $izena;
    public ?string $taldea;

    public function __construct(int $id, string $izena, ?string $taldea)
    {
        $this->id = $id;
        $this->izena = $izena;
        $this->taldea = $taldea;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getIzena(): string { return $this->izena; }
    public function getTaldea(): ?string { return $this->taldea; }

    // CRUD
    public static function getAll(): array
    {
        $conn = DB::getConnection();
        $result = $conn->query("SELECT id, izena, taldea FROM gela");
        $gelak = [];
        while ($row = $result->fetch_assoc()) {
            $gelak[] = new Gela((int)$row['id'], $row['izena'], $row['taldea']);
        }
        return $gelak;
    }

    public static function getById(int $id): ?Gela
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("SELECT id, izena, taldea FROM gela WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? new Gela((int)$row['id'], $row['izena'], $row['taldea']) : null;
    }

    public static function create(string $izena, ?string $taldea): ?Gela
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("INSERT INTO gela (izena, taldea) VALUES (?, ?)");
        $stmt->bind_param("ss", $izena, $taldea);
        $success = $stmt->execute();
        if ($success) {
            $id = $conn->insert_id;
            $stmt->close();
            return new Gela($id, $izena, $taldea);
        }
        $stmt->close();
        return null;
    }

    public function delete(): bool
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("DELETE FROM gela WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function toArray(): array
    {
        return ['id' => $this->id, 'izena' => $this->izena, 'taldea' => $this->taldea];
    }
}
