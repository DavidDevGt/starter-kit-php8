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
    public $active;
    public $created_at;
    public $updated_at;

    // EJEMPLO DE USO RELACIONES
    // Método para obtener los posts de un usuario
    // public function posts()
    // {
    //     return $this->hasMany(Post::class, 'user_id');
    // }
}
