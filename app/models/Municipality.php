<?php

namespace App\Models;

use App\Config\Database;

class Municipality extends Model
{
    protected $table = 'municipality';

    public $id;
    public $departament_id;
    public $code;
    public $name;
    public $active;
    public $created_at;
    public $updated_at;
}
