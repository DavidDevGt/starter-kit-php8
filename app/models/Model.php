<?php

namespace App\Models;

use App\Config\Database;

abstract class Model {
    protected $table;
    protected $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->connect();
    }
    public function find($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result) {
            $this->fill($result);
        }

        return $this;
    }

    public function save() {
        //* Falta terminar
    }

    protected function fill($data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    // Método genérico para obtener relaciones
    // $relatedModel es el nombre de la clase del modelo relacionado
    // $foreignKey es la clave en la tabla relacionada que apunta a este modelo
    public function related($relatedModel, $foreignKey) {
        $relatedInstance = new $relatedModel();
        $relatedTable = $relatedInstance->table;

        $stmt = $this->conn->prepare("SELECT * FROM {$relatedTable} WHERE {$foreignKey} = ?");
        //* Sin terminar
    }
}
