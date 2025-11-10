<?php
declare(strict_types=1);
require_once "../controladores/conexion.php";

class Gestioa {
    private string $ekipamenduIzena;
    private ?string $gelaIzena;
    private ?string $taldea;

    public function __construct(string $ekipamenduIzena, ?string $gelaIzena, ?string $taldea)
    {
        $this->ekipamenduIzena = $ekipamenduIzena;
        $this->gelaIzena = $gelaIzena;
        $this->taldea = $taldea;
    }

    public function toArray(): array {
        return [
            "ekipamendu_izena" => $this->ekipamenduIzena,
            "gela_izena" => $this->gelaIzena ?? "",
            "taldea" => $this->taldea ?? ""
        ];
    }

    public static function getAll(): array
    {
        $conn = DB::getConnection();

        $sql = "
            SELECT e.izena AS ekipamendu, g.izena AS gela, g.taldea AS taldea
            FROM ekipamendua e
            LEFT JOIN inbentarioa i ON i.idEkipamendu = e.id
            LEFT JOIN kokalekua k ON k.etiketa = i.etiketa
            LEFT JOIN gela g ON g.id = k.idGela
        ";

        $result = $conn->query($sql);
        if (!$result) throw new Exception("Errorea kontsultan: " . $conn->error);

        $gestioak = [];
        while ($row = $result->fetch_assoc()) {
            $gestioak[] = new Gestioa(
                $row['ekipamendu'],
                $row['gela'],
                $row['taldea']
            );
        }

        $result->free();
        return $gestioak;
    }
}