<?php

namespace App\Models;

use App\Config\Database;

class Country extends Model
{
    protected $table = 'country';

    public $id;
    public $code;
    public $name;
    public $is_active;
    public $currency_symbol;
    public $active;
    public $created_at;
    public $updated_at;

    public function companies()
    {
        return $this->hasMany(Company::class, 'company_country_id', 'id');
    }

    public function departaments()
    {
        return $this->hasMany(Departament::class, 'country_id', 'id');
    }

    public function taxes()
{
    return $this->hasMany(Tax::class, 'country_id', 'id');
}
}
