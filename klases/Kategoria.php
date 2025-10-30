<?php
class Kategoria {
    public $id;
    public $nombre;
    private $mysqli;

    public function __construct($id, $nombre, $mysqli) {
        $this->id = $id;
        $this->nombre = $nombre;
        $this->mysqli = $mysqli;
    }

    // Guardar nueva categoría
    public function guardar() {
        $stmt = $this->mysqli->prepare("INSERT INTO kategoria (izena) VALUES (?)");
        if (!$stmt) throw new Exception($this->mysqli->error);
        $stmt->bind_param("s", $this->nombre);
        if (!$stmt->execute()) throw new Exception($stmt->error);
        $this->id = $stmt->insert_id;
        $stmt->close();
    }

    // Obtener todas las categorías
    public static function obtenerTodos($mysqli) {
        $result = $mysqli->query("SELECT * FROM kategoria");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Buscar categoría por ID
    public static function buscarPorId($id, $mysqli) {
        $stmt = $mysqli->prepare("SELECT * FROM kategoria WHERE id=?");
        if (!$stmt) return null;
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        return $res ? new Kategoria($res['id'], $res['izena'], $mysqli) : null;
    }

    // Actualizar categoría
    public function actualizar() {
        $stmt = $this->mysqli->prepare("UPDATE kategoria SET izena=? WHERE id=?");
        $stmt->bind_param("si", $this->nombre, $this->id);
        $stmt->execute();
        $stmt->close();
    }

    // Eliminar categoría
    public function eliminar() {
        $stmt = $this->mysqli->prepare("DELETE FROM kategoria WHERE id=?");
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $stmt->close();
    }
}
