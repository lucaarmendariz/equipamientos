<?php
declare(strict_types=1);

require_once "../controladores/conexion.php";

class Kokaleku
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
    $sql = "SELECT k.etiketa, k.idGela, g.izena AS gela_izena, g.taldea,
                   i.idEkipamendu, e.izena AS ekipamendu,
                   k.hasieraData, k.amaieraData
            FROM kokalekua k
            JOIN gela g ON k.idGela = g.id
            JOIN inbentarioa i ON k.etiketa = i.etiketa
            JOIN ekipamendua e ON i.idEkipamendu = e.id
            WHERE k.amaieraData IS NULL
            ORDER BY g.izena ASC";
    $res = $conn->query($sql);
    $data = [];
    while ($row = $res->fetch_assoc())
        $data[] = $row;
    return $data;
}


    public static function getAllAmaieraData(?int $idGela = null, ?string $fechaInicio = null, ?string $fechaFin = null, int $offset = 0, int $limit = 50): array
{
    $conn = DB::getConnection();

    $sql = "SELECT k.etiketa, k.idGela, g.izena AS gela_izena, g.taldea,
                   i.idEkipamendu, e.izena AS ekipamendu,
                   k.hasieraData, k.amaieraData
            FROM kokalekua k
            JOIN gela g ON k.idGela = g.id
            JOIN inbentarioa i ON k.etiketa = i.etiketa
            JOIN ekipamendua e ON i.idEkipamendu = e.id
            WHERE k.amaieraData IS NOT NULL";

    $params = [];
    $types = "";

    if ($idGela) {
        $sql .= " AND k.idGela=?";
        $types .= "i";
        $params[] = $idGela;
    }
    if ($fechaInicio) {
        $sql .= " AND k.hasieraData>=?";
        $types .= "s";
        $params[] = $fechaInicio;
    }
    if ($fechaFin) {
        $sql .= " AND k.amaieraData<=?";
        $types .= "s";
        $params[] = $fechaFin;
    }

    $sql .= " ORDER BY k.amaieraData DESC LIMIT ?, ?";
    $types .= "ii";
    $params[] = $offset;
    $params[] = $limit;

    $stmt = $conn->prepare($sql);
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();

    return $data;
}



    public static function getById(string $etiketa): ?array
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("SELECT k.etiketa, k.idGela, g.izena AS gela_izena, g.taldea,
                                       i.idEkipamendu, e.izena AS ekipamendu,
                                       k.hasieraData, k.amaieraData
                                FROM kokalekua k
                                JOIN gela g ON k.idGela = g.id
                                JOIN inbentarioa i ON k.etiketa = i.etiketa
                                JOIN ekipamendua e ON i.idEkipamendu = e.id
                                WHERE k.etiketa = ?");
        $stmt->bind_param("s", $etiketa);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ?: null;
    }

    public static function create(int $idGela, int $idEkipamendu, int $cantidad, string $hasieraData = null, ?string $amaieraData = null): bool
    {
        $conn = DB::getConnection();
        $hasieraData = $hasieraData ?? date("Y-m-d");

        $stmt = $conn->prepare("SELECT etiketa FROM inbentarioa WHERE idEkipamendu=? LIMIT ?");
        $stmt->bind_param("ii", $idEkipamendu, $cantidad);
        $stmt->execute();
        $res = $stmt->get_result();
        $etiketas = [];
        while ($row = $res->fetch_assoc())
            $etiketas[] = $row['etiketa'];
        $stmt->close();

        if (count($etiketas) < $cantidad)
            return false;

        $stmt = $conn->prepare("INSERT INTO kokalekua (etiketa, idGela, hasieraData, amaieraData) VALUES (?, ?, ?, ?)");
        foreach ($etiketas as $etiketa) {
            $stmt->bind_param("siss", $etiketa, $idGela, $hasieraData, $amaieraData);
            $stmt->execute();
        }
        $stmt->close();
        return true;
    }

    public static function update(string $etiketa, int $idGela, ?string $amaieraData = null): bool
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("UPDATE kokalekua SET idGela=?, amaieraData=? WHERE etiketa=?");
        $stmt->bind_param("iss", $idGela, $amaieraData, $etiketa);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }



    public static function delete(string $etiketa, string $hasieraData, string $amaieraData): bool
{
    $conn = DB::getConnection();

    // Actualiza solo la columna amaieraData para marcar la finalización
    $stmt = $conn->prepare("UPDATE kokalekua SET amaieraData=? WHERE etiketa=? AND hasieraData=?");
    $stmt->bind_param("sss", $amaieraData, $etiketa, $hasieraData);

    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

}
