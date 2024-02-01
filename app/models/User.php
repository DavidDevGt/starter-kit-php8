<?php

namespace App\Models;

use App\Config\Database;

class User extends Model
{
    protected $table = 'user';

    public $id;
    public $username;
    public $password;
    public $email;
    public $role_id;
    public $active;
    public $created_at;
    public $updated_at;

    // Verificar las credenciales del usuario
    public function verifyCredentials($username, $password)
    {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE username = ? AND active = TRUE LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result && password_verify($password, $result['password'])) {
            $this->fill($result);
            return $this;
        } else {
            return null;
        }
    }

    public function role()
    {
        return $this->hasOne(Role::class, 'id', 'role_id');
    }

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'user_id', 'id');
    }

    public function employeeData()
    {
        return $this->hasOne(EmployeeData::class, 'user_id', 'id');
    }
}
