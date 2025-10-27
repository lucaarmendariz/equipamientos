<?php
declare(strict_types=1);
require_once "../controladores/conexion.php"; // aquí está la clase DB

class Erabiltzailea
{
    private string $nan;
    private string $izena;
    private string $abizena;
    private string $erabiltzailea;
    private string $pasahitza;
    private string $rola;

    public function __construct(string $nan, string $izena, string $abizena, string $erabiltzailea, string $pasahitza, string $rola)
    {
        $this->nan = $nan;
        $this->izena = $izena;
        $this->abizena = $abizena;
        $this->erabiltzailea = $erabiltzailea;
        $this->pasahitza = $pasahitza;
        $this->rola = $rola;
    }

    // Getters
    public function getNan(): string { return $this->nan; }
    public function getIzena(): string { return $this->izena; }
    public function getAbizena(): string { return $this->abizena; }
    public function getErabiltzailea(): string { return $this->erabiltzailea; }
    public function getPasahitza(): string { return $this->pasahitza; }
    public function getRola(): string { return $this->rola; }

    // CRUD
    public static function getAll(): array
    {
        $conn = DB::getConnection();
        $result = $conn->query("SELECT * FROM erabiltzailea");
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = new Erabiltzailea($row['nan'], $row['izena'], $row['abizena'], $row['erabiltzailea'], $row['pasahitza'], $row['rola']);
        }
        return $users;
    }

    public static function getByNan(string $nan): ?Erabiltzailea
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("SELECT * FROM erabiltzailea WHERE nan = ?");
        $stmt->bind_param("s", $nan);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? new Erabiltzailea($row['nan'], $row['izena'], $row['abizena'], $row['erabiltzailea'], $row['pasahitza'], $row['rola']) : null;
    }

    public static function create(string $nan, string $izena, string $abizena, string $erabiltzailea, string $pasahitza, string $rola): ?Erabiltzailea
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("INSERT INTO erabiltzailea (nan, izena, abizena, erabiltzailea, pasahitza, rola) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $nan, $izena, $abizena, $erabiltzailea, $pasahitza, $rola);
        $success = $stmt->execute();
        $stmt->close();
        return $success ? new Erabiltzailea($nan, $izena, $abizena, $erabiltzailea, $pasahitza, $rola) : null;
    }

    public function delete(): bool
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("DELETE FROM erabiltzailea WHERE nan = ?");
        $stmt->bind_param("s", $this->nan);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function toArray(): array
    {
        return [
            'nan' => $this->nan,
            'izena' => $this->izena,
            'abizena' => $this->abizena,
            'erabiltzailea' => $this->erabiltzailea,
            'pasahitza' => $this->pasahitza,
            'rola' => $this->rola
        ];
    }
}
