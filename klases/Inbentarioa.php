<?php
class Inbentarioa {
    private $mysqli;
    public $etiketa;
    public $idEkipamendu;
    public $erosketaData;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    // ================= Métodos de lectura =================

    // Obtener todos los registros
  public function getAll() {
    $sql = "
        SELECT 
            i.etiketa, 
            e.izena AS izena, 
            g.taldea AS kokaleku
        FROM inbentarioa i
        LEFT JOIN ekipamendua e ON i.idEkipamendu = e.id
        LEFT JOIN kokalekua k ON i.etiketa = k.etiketa
        LEFT JOIN gela g ON k.idGela = g.id
        ORDER BY i.etiketa ASC
    ";

    $result = $this->mysqli->query($sql);

    if (!$result) {
        throw new Exception('SQL error: ' . $this->mysqli->error);
    }

    $inventario = [];
    while ($row = $result->fetch_assoc()) {
        $inventario[] = $row;
    }

    return $inventario;
}
}
?>
