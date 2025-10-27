<?php
declare(strict_types=1);
require_once "../controladores/conexion.php"; // aquí está la clase DB

class Ekipamendua
{
    public int $id;
    public string $izena;
    public string $deskribapena;
    public ?string $marka;
    public ?string $modelo;
    public int $stock;
    public int $idKategoria;

    public function __construct(
        int $id,
        string $izena,
        string $deskribapena,
        ?string $marka,
        ?string $modelo,
        int $stock,
        int $idKategoria
    ) {
        $this->id = $id;
        $this->izena = $izena;
        $this->deskribapena = $deskribapena;
        $this->marka = $marka;
        $this->modelo = $modelo;
        $this->stock = $stock;
        $this->idKategoria = $idKategoria;
    }

    // Getters
    public function getId(): int
    {
        return $this->id;
    }
    public function getIzena(): string
    {
        return $this->izena;
    }
    public function getDeskribapena(): string
    {
        return $this->deskribapena;
    }
    public function getMarka(): ?string
    {
        return $this->marka;
    }
    public function getModelo(): ?string
    {
        return $this->modelo;
    }
    public function getStock(): int
    {
        return $this->stock;
    }
    public function getIdKategoria(): int
    {
        return $this->idKategoria;
    }

    // ===============================
    // 🔹 CRUD Methods
    // ===============================

    public static function getById(int $id): ?Ekipamendua
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("SELECT * FROM ekipamendua WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row)
            return null;

        return new Ekipamendua(
            (int) $row['id'],
            $row['izena'],
            $row['deskribapena'],
            $row['marka'],
            $row['modelo'],
            (int) $row['stock'],
            (int) $row['idKategoria']
        );
    }

    public static function create(
        string $izena,
        string $deskribapena,
        ?string $marka,
        ?string $modelo,
        int $stock,
        int $idKategoria
    ): ?Ekipamendua {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("
        INSERT INTO ekipamendua (izena, deskribapena, marka, modelo, stock, idKategoria)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
        $stmt->bind_param("ssssii", $izena, $deskribapena, $marka, $modelo, $stock, $idKategoria);
        $success = $stmt->execute();

        if ($success) {
            $id = $conn->insert_id;
            $stmt->close();
            return new Ekipamendua($id, $izena, $deskribapena, $marka, $modelo, $stock, $idKategoria);
        } else {
            $stmt->close();
            return null;
        }
    }



    public function update(): bool
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("
            UPDATE ekipamendua
            SET izena=?, deskribapena=?, marka=?, modelo=?, stock=?, idKategoria=?
            WHERE id=?
        ");
        $stmt->bind_param(
            "ssssiii",
            $this->izena,
            $this->deskribapena,
            $this->marka,
            $this->modelo,
            $this->stock,
            $this->idKategoria,
            $this->id
        );
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function delete(): bool
    {
        $conn = DB::getConnection();
        $stmt = $conn->prepare("DELETE FROM ekipamendua WHERE id=?");
        $stmt->bind_param("i", $this->id);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'izena' => $this->izena,
            'deskribapena' => $this->deskribapena,
            'marka' => $this->marka,
            'modelo' => $this->modelo,
            'stock' => $this->stock,
            'idKategoria' => $this->idKategoria
        ];
    }
}
