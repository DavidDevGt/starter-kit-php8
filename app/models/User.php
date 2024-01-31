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
            // Si la contraseña coincide, llenar el modelo con los datos del usuario y retornarlo
            $this->fill($result);
            return $this; // Retorna la instancia del modelo User con los datos del usuario autenticado
        } else {
            // Si las credenciales no coinciden o el usuario no existe, retornar null
            return null;
        }
    }

    // EJEMPLO DE USO RELACIONES
    // Método para obtener los posts de un usuario
    // public function posts()
    // {
    //     return $this->hasMany(Post::class, 'user_id');
    // }
}
