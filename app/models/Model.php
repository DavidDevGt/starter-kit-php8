<?php

namespace App\Models;

ini_set('log_errors', 1);
ini_set('error_log', './errors.log');
error_reporting(E_ALL);

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
        unset($attributes['conn'], $attributes['table']);

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
            $values[] = $this->id;
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

    /**
     * Recupera todos los registros de la tabla asociada al modelo.
     *
     * @return array Una lista de instancias del modelo con todos los registros de la tabla.
     */
    public function all()
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE active = TRUE");
        $stmt->execute();
        $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Crear una lista para almacenar las instancias del modelo
        $models = [];

        // Rellenar cada modelo con los datos de la base de datos
        foreach ($results as $result) {
            $model = new static(); // Crear una nueva instancia del modelo concreto
            $model->fill($result);
            $models[] = $model;
        }

        return $models;
    }

    public function delete()
    {
        if ($this->id) {
            $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id = ?");
            $stmt->bind_param('i', $this->id);
            $stmt->execute();
        }
    }

    public function softDelete()
    {
        if ($this->id) {
            $stmt = $this->conn->prepare("UPDATE {$this->table} SET active = FALSE WHERE id = ?");
            $stmt->bind_param('i', $this->id);
            $stmt->execute();
        }
    }

    //* Relaciones de tablas *//
    // 1:N

    public function hasMany($relatedModel, $foreignKey, $localKey = 'id')
    {
        $relatedInstance = new $relatedModel();
        $relatedTable = $relatedInstance->table;

        $stmt = $this->conn->prepare("SELECT * FROM {$relatedTable} WHERE {$foreignKey} = ?");
        $stmt->bind_param('i', $this->$localKey);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // 1:1
    public function hasOne($relatedModel, $foreignKey, $localKey = 'id')
    {
        $relatedInstance = new $relatedModel();
        $relatedTable = $relatedInstance->table;

        $stmt = $this->conn->prepare("SELECT * FROM {$relatedTable} WHERE {$foreignKey} = ? LIMIT 1");
        $stmt->bind_param('i', $this->$localKey);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // N:M (usar una tabla intermedia)
    public function belongsToMany($relatedModel, $pivotTable, $foreignKey, $relatedKey, $localKey = 'id')
    {
        $relatedInstance = new $relatedModel();
        $relatedTable = $relatedInstance->table;

        $stmt = $this->conn->prepare("SELECT {$relatedTable}.* FROM {$relatedTable} INNER JOIN {$pivotTable} ON {$pivotTable}.{$relatedKey} = {$relatedTable}.id WHERE {$pivotTable}.{$foreignKey} = ?");
        $stmt->bind_param('i', $this->$localKey);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
