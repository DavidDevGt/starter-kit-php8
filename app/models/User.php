<?php

namespace App\Models;

use App\Config\Database;

class User {
    protected $table = 'user';

    public $id;
    public $username;
    public $password;
    public $email;
    public $active;
    public $created_at;
    public $updated_at;

    
}