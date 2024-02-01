<?php

namespace App\Models;

use App\Config\Database;

class Permission extends Model
{
    protected $table = 'permission';

    public $id;
    public $user_id;
    public $module_id;
    public $active;
    public $created_at;
    public $updated_at;

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function module()
    {
        return $this->hasOne(Module::class, 'id', 'module_id');
    }
}
