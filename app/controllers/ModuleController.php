<?php

namespace App\Controllers;

use App\Models\Module;

class ModuleController {
    public function index()
    {
        $role = new Module();
        return $role->all();
    }

    public function store($data)
    {
        $role = new Module();
        foreach ($data as $key => $value) {
            $role->$key = $value;
        }
        $role->save();
    }

    public function update($id, $data)
    {
        $role = (new Module())->find($id);
        foreach ($data as $key => $value) {
            $role->$key = $value;
        }
        $role->save();
    }

    public function show($id)
    {
        $role = (new Module())->find($id);
        return $role;
    }

    public function delete($id)
    {
        $role = (new Module())->find($id);
        $role->softDelete();
    }
}