<?php
declare(strict_types=1);

require_once "../controladores/conexion.php";

class Kokaleku {
    public string $etiketa;
    public int $idGela;
    public string $hasieraData;
    public ?string $amaieraData; // Nuevo atributo opcional

    public function __construct(string $etiketa, int $idGela, string $hasieraData, ?string $amaieraData = null) {
        $this->etiketa = $etiketa;
        $this->idGela = $idGela;
        $this->hasieraData = $hasieraData;
        $this->amaieraData = $amaieraData;
    }

    // Listar todos los kokalekus con info de gela y equipo
    public static function getAll(): array {
        $conn = DB::getConnection();
        $sql = "SELECT k.etiketa, k.idGela, g.izena AS gela_izena, g.taldea, 
                       i.idEkipamendu, e.izena AS ekipamendu,
                       k.hasieraData, k.amaieraData
                FROM kokalekua k
                JOIN gela g ON k.idGela = g.id
                JOIN inbentarioa i ON k.etiketa = i.etiketa
                JOIN ekipamendua e ON i.idEkipamendu = e.id
                ORDER BY g.izena ASC";
        $res = $conn->query($sql);
        $data = [];
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    // Crear múltiples kokalekus según la cantidad indicada (no se guarda la cantidad)
    public static function create(int $idGela, int $idEkipamendu, int $cantidad, string $hasieraData = null, ?string $amaieraData = null): bool {
        $conn = DB::getConnection();
        $hasieraData = $hasieraData ?? date("Y-m-d");

        // Obtener etiquetas disponibles
        $stmt = $conn->prepare("SELECT etiketa FROM inbentarioa WHERE idEkipamendu=? LIMIT ?");
        $stmt->bind_param("ii", $idEkipamendu, $cantidad);
        $stmt->execute();
        $res = $stmt->get_result();
        $etiketas = [];
        while ($row = $res->fetch_assoc()) $etiketas[] = $row['etiketa'];
        $stmt->close();

        if (count($etiketas) < $cantidad) return false; // no hay suficientes unidades

        $stmt = $conn->prepare("INSERT INTO kokalekua (etiketa, idGela, hasieraData, amaieraData) VALUES (?, ?, ?, ?)");
        foreach ($etiketas as $etiketa) {
            $stmt->bind_param("siss", $etiketa, $idGela, $hasieraData, $amaieraData);
            $stmt->execute();
        }
        $stmt->close();
        return true;
    }

    // Eliminar kokaleku por etiketa, hasieraData y opcionalmente amaieraData
    public static function delete(string $etiketa, string $hasieraData, ?string $amaieraData = null): bool {
        $conn = DB::getConnection();

        if ($amaieraData !== null) {
            $stmt = $conn->prepare("DELETE FROM kokalekua WHERE etiketa=? AND hasieraData=? AND amaieraData=?");
            $stmt->bind_param("sss", $etiketa, $hasieraData, $amaieraData);
        } else {
            $stmt = $conn->prepare("DELETE FROM kokalekua WHERE etiketa=? AND hasieraData=?");
            $stmt->bind_param("ss", $etiketa, $hasieraData);
        }

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

}
