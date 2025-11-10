<?php
declare(strict_types=1);

require_once __DIR__ . '/../controladores/conexion.php';

class Kokalekua
{
    public string $etiketa;
    public int $idGela;
    public string $hasieraData;
    public ?string $amaieraData;

    public function __construct(string $etiketa, int $idGela, string $hasieraData, ?string $amaieraData = null)
    {
        $this->etiketa = $etiketa;
        $this->idGela = $idGela;
        $this->hasieraData = $hasieraData;
        $this->amaieraData = $amaieraData;
    }

    public static function getAll(): array
    {
        $conn = DB::getConnection();
        $sql = "
            SELECT k.etiketa, k.idGela, g.izena AS gela_izena, g.taldea, 
                   k.hasieraData, k.amaieraData
            FROM kokalekua k
            INNER JOIN gela g ON k.idGela = g.id
            ORDER BY k.hasieraData DESC
        ";
        $res = $conn->query($sql);
        $kokalekuak = [];
        while ($row = $res->fetch_assoc()) {
            $kokalekuak[] = $row;
        }
        return $kokalekuak;
    }

    public static function getByEtiketa(string $etiketa): ?Kokalekua
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("SELECT * FROM kokalekua WHERE etiketa = ?");
        $stmt->bind_param("s", $etiketa);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        if (!$result) return null;

        return new Kokalekua($result['etiketa'], (int)$result['idGela'], $result['hasieraData'], $result['amaieraData']);
    }

    public static function create(string $etiketa, int $idGela, string $hasieraData): ?Kokalekua
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("INSERT INTO kokalekua (etiketa, idGela, hasieraData) VALUES (?, ?, ?)");
        $stmt->bind_param("sis", $etiketa, $idGela, $hasieraData);

        if ($stmt->execute()) {
            return new Kokalekua($etiketa, $idGela, $hasieraData);
        }
        return null;
    }

    public static function delete(string $etiketa, string $hasieraData): bool
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("DELETE FROM kokalekua WHERE etiketa = ? AND hasieraData = ?");
        $stmt->bind_param("ss", $etiketa, $hasieraData);
        return $stmt->execute();
    }

    public function toArray(): array
    {
        return [
            'etiketa' => $this->etiketa,
            'idGela' => $this->idGela,
            'hasieraData' => $this->hasieraData,
            'amaieraData' => $this->amaieraData,
        ];
    }
}
