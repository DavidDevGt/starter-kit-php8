<?php

namespace App\Models;

use App\Config\Database;

class User extends Model {
    protected $table = 'user';

    public $id;
    public $username;
    public $password;
    public $email;
    public $active;
    public $created_at;
    public $updated_at;

    
}