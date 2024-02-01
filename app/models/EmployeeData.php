<?php

namespace App\Models;

use App\Config\Database;

class EmployeeData extends Model
{
    protected $table = 'employee_data';

    public $id;
    public $user_id;
    public $name;
    public $last_name;
    public $phone;
    public $address;
    public $active;
    public $created_at;
    public $updated_at;

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
