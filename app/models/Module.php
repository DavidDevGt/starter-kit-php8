<?php

namespace App\Models;

use App\Config\Database;

class Module extends Model
{
    protected $table = 'module';

    public $id;
    public $order;
    public $name;
    public $primary_module;
    public $father_module_id;
    public $route;
    public $active;
    public $created_at;
    public $updated_at;

    public function permissions()
    {
        return $this->hasMany(Permission::class, 'module_id', 'id');
    }
}
