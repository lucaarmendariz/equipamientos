<?php
declare(strict_types=1);
require_once "../controladores/conexion.php"; // aquí está la clase DB

class Kokalekua
{
    private string $etiketa;
    private int $idGela;
    private string $hasieraData;
    private ?string $amaieraData;

    public function __construct(string $etiketa, int $idGela, string $hasieraData, ?string $amaieraData)
    {
        $this->etiketa = $etiketa;
        $this->idGela = $idGela;
        $this->hasieraData = $hasieraData;
        $this->amaieraData = $amaieraData;
    }

    // Getters
    public function getEtiketa(): string { return $this->etiketa; }
    public function getIdGela(): int { return $this->idGela; }
    public function getHasieraData(): string { return $this->hasieraData; }
    public function getAmaieraData(): ?string { return $this->amaieraData; }

    // CRUD
    public static function getByEtiketa(string $etiketa): array
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("SELECT etiketa, idGela, hasieraData, amaieraData FROM kokalekua WHERE etiketa = ?");
        $stmt->bind_param("s", $etiketa);
        $stmt->execute();
        $result = $stmt->get_result();
        $kokalekuak = [];
        while ($row = $result->fetch_assoc()) {
            $kokalekuak[] = new Kokalekua($row['etiketa'], (int)$row['idGela'], $row['hasieraData'], $row['amaieraData']);
        }
        $stmt->close();
        return $kokalekuak;
    }

    public static function create(string $etiketa, int $idGela, string $hasieraData, ?string $amaieraData): ?Kokalekua
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("INSERT INTO kokalekua (etiketa, idGela, hasieraData, amaieraData) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("siss", $etiketa, $idGela, $hasieraData, $amaieraData);
        $success = $stmt->execute();
        $stmt->close();
        return $success ? new Kokalekua($etiketa, $idGela, $hasieraData, $amaieraData) : null;
    }

    public function delete(): bool
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("DELETE FROM kokalekua WHERE etiketa = ? AND hasieraData = ?");
        $stmt->bind_param("ss", $this->etiketa, $this->hasieraData);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function toArray(): array
    {
        return [
            'etiketa' => $this->etiketa,
            'idGela' => $this->idGela,
            'hasieraData' => $this->hasieraData,
            'amaieraData' => $this->amaieraData
        ];
    }
}
