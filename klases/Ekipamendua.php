<?php
class Ekipamendua {
    private $mysqli;
    public $id;
    public $izena;
    public $marka;      
    public $modelo;
    public $stock;
    public $idKategoria;

    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
    }

    // Crear
    public function create($data) {
        $stmt = $this->mysqli->prepare("INSERT INTO ekipamendua (izena, marka, modelo, stock, idKategoria) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "sssii",
            $data['izena'],
            $data['marka'],
            $data['modelo'],
            $data['stock'],
            $data['idKategoria']
        );
        if (!$stmt->execute()) {
            throw new Exception("Error al crear equipo: ".$stmt->error);
        }
        return $this->mysqli->insert_id;
    }

    // Actualizar parcial
    public function update($id, $data) {
        $fields = [];
        $params = [];
        $types = '';

        if (array_key_exists('izena', $data)) {
            $fields[] = 'izena=?';
            $params[] = $data['izena'];
            $types .= 's';
        }
        if (array_key_exists('marka', $data)) {
            $fields[] = 'marka=?';
            $params[] = $data['marka'];
            $types .= 's';
        }
        if (array_key_exists('modelo', $data)) {
            $fields[] = 'modelo=?';
            $params[] = $data['modelo'];
            $types .= 's';
        }
        if (array_key_exists('stock', $data)) {
            $fields[] = 'stock=?';
            $params[] = $data['stock'];
            $types .= 'i';
        }
        if (array_key_exists('idKategoria', $data)) {
            $fields[] = 'idKategoria=?';
            $params[] = $data['idKategoria'];
            $types .= 'i';
        }

        if (empty($fields)) return false;

        $sql = "UPDATE ekipamendua SET ".implode(',', $fields)." WHERE id=?";
        $params[] = $id;
        $types .= 'i';

        $stmt = $this->mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }

    // Eliminar
    public function delete($id) {
        $stmt = $this->mysqli->prepare("DELETE FROM ekipamendua WHERE id=?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Obtener todos
    public function getAll() {
        $result = $this->mysqli->query("
            SELECT e.id, e.izena, e.marka, e.modelo, e.stock, k.izena AS kategoria, e.idKategoria
            FROM ekipamendua e
            LEFT JOIN kategoria k ON e.idKategoria = k.id
            ORDER BY e.izena ASC
        ");

        $equipos = [];
        while ($row = $result->fetch_assoc()) {
            if (!isset($row['marka'])) $row['marka'] = '';
            $equipos[] = $row;
        }
        return $equipos;
    }

    // Buscar por ID
    public function findById($id) {
        $stmt = $this->mysqli->prepare("SELECT * FROM ekipamendua WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if ($row && !isset($row['marka'])) $row['marka'] = '';
        return $row ?: null;
    }
}
?>
