<?php
declare(strict_types=1);
require_once "../controladores/conexion.php"; // aquí está la clase DB

class Inbentarioa
{
    private string $etiketa;
    private int $idEkipamendu;
    private string $erosketaData;

    public function __construct(string $etiketa, int $idEkipamendu, string $erosketaData)
    {
        $this->etiketa = $etiketa;
        $this->idEkipamendu = $idEkipamendu;
        $this->erosketaData = $erosketaData;
    }

    // Getters
    public function getEtiketa(): string { return $this->etiketa; }
    public function getIdEkipamendu(): int { return $this->idEkipamendu; }
    public function getErosketaData(): string { return $this->erosketaData; }

    // CRUD
    public static function getAll(): array
    {
        $conn = DB::getConnection();
        $result = $conn->query("SELECT etiketa, idEkipamendu, erosketadata FROM inbentarioa");
        $inb = [];
        while ($row = $result->fetch_assoc()) {
            $inb[] = new Inbentarioa($row['etiketa'], (int)$row['idEkipamendu'], $row['erosketadata']);
        }
        return $inb;
    }

    public static function getByEtiketa(string $etiketa): ?Inbentarioa
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("SELECT etiketa, idEkipamendu, erosketadata FROM inbentarioa WHERE etiketa = ?");
        $stmt->bind_param("s", $etiketa);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? new Inbentarioa($row['etiketa'], (int)$row['idEkipamendu'], $row['erosketadata']) : null;
    }

    public static function create(string $etiketa, int $idEkipamendu, string $erosketaData): ?Inbentarioa
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("INSERT INTO inbentarioa (etiketa, idEkipamendu, erosketadata) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $etiketa, $idEkipamendu, $erosketaData);
        $success = $stmt->execute();
        $stmt->close();
        return $success ? new Inbentarioa($etiketa, $idEkipamendu, $erosketaData) : null;
    }

    public function delete(): bool
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("DELETE FROM inbentarioa WHERE etiketa = ?");
        $stmt->bind_param("s", $this->etiketa);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function toArray(): array
    {
        return [
            'etiketa' => $this->etiketa,
            'idEkipamendu' => $this->idEkipamendu,
            'erosketaData' => $this->erosketaData
        ];
    }
}
