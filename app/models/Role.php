<?php

namespace App\Models;

use App\Config\Database;

class Role extends Model
{
    protected $table = 'role';

    public $id;
    public $code;
    public $name;
    public $description;
    public $active;
    public $created_at;
    public $updated_at;

    public function users()
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }
}
