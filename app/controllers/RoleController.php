<?php

namespace App\Controllers;

use App\Models\Role;

class RoleController
{
    public function index()
    {
        $role = new Role();
        return $role->all();
    }

    public function store($data)
    {
        $role = new Role();
        foreach ($data as $key => $value) {
            $role->$key = $value;
        }
        $role->save();
    }

    public function update($id, $data)
    {
        $role = (new Role())->find($id);
        foreach ($data as $key => $value) {
            $role->$key = $value;
        }
        $role->save();
    }

    public function show($id)
    {
        $role = (new Role())->find($id);
        return $role;
    }

    public function delete($id)
    {
        $role = (new Role())->find($id);
        $role->softDelete();
    }
}
