<?php

namespace App\Models;

use App\Config\Database;

class Tax extends Model
{
    protected $table = 'tax';

    public $id;
    public $company_id;
    public $code;
    public $name;
    public $country_id;
    public $value;
    public $active;
    public $created_at;
    public $updated_at;
}
