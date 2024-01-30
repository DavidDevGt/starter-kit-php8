<?php

namespace App\Models;

use App\Config\Database;

abstract class Model
{
    protected $table;
    protected $conn;
    protected $id;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->connect();
    }
    public function find($id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result) {
            $this->fill($result);
        }

        return $this;
    }

    public function save()
    {
        $attributes = get_object_vars($this);
        unset($attributes['conn'], $attributes['table']); // Excluir la conexión y el nombre de la tabla

        if ($this->id) {
            // Actualizar
            $setParts = [];
            $values = [];
            foreach ($attributes as $key => $value) {
                if ($key !== 'id') {
                    $setParts[] = "{$key} = ?";
                    $values[] = $value;
                }
            }
            $values[] = $this->id; // Agregar 'id' al final para la condición WHERE
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET " . implode(', ', $setParts) . " WHERE id = ?");
        } else {
            // Insertar
            $columns = implode(', ', array_keys($attributes));
            $placeholders = implode(', ', array_fill(0, count($attributes), '?'));
            $values = array_values($attributes);
            $stmt = $this->conn->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})");
        }

        $stmt->bind_param(str_repeat('s', count($values)), ...$values);
        $stmt->execute();

        if (!$this->id) {
            $this->id = $stmt->insert_id;
        }
    }

    protected function fill($data)
    {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    // Método genérico para obtener relaciones
    // $relatedModel es el nombre de la clase del modelo relacionado
    // $foreignKey es la clave en la tabla relacionada que apunta a este modelo
    public function related($relatedModel, $foreignKey)
    {
        $relatedInstance = new $relatedModel();
        $relatedTable = $relatedInstance->table;

        $stmt = $this->conn->prepare("SELECT * FROM {$relatedTable} WHERE {$foreignKey} = ?");
        //* Sin terminar
    }
}
