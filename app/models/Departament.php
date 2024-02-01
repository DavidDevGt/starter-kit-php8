<?php

namespace App\Models;

use App\Config\Database;

class Departament extends Model
{
    protected $table = 'department';

    public $id;
    public $code;
    public $name;
    public $country_id;
    public $active;
    public $created_at;
    public $updated_at;
}
