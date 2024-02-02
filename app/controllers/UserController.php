<?php

namespace App\Controllers;

use App\Models\User;
use App\Config\Database;

class UserController
{
    public function index()
    {
        // Mostrar una lista de usuarios
        $database = new Database();
        $users = $database->dbQuery('SELECT * FROM users');

        if ($users) {
            return json_encode([
                'success' => true,
                'users' => $users
            ]);
        } else {
            // Manejo del error en caso de que no se encuentren usuarios
            return json_encode([
                'success' => false,
                'message' => 'No se encontraron usuarios.'
            ]);
        }
    }

    public function show($id)
    {
        // Mostrar un usuario específico
        $user = new User();
        $user = $user->find($id);
        // Cargar data en formato json
        if ($user->id) {
            return json_encode([
                'success' => true,
                'user' => $user
            ]);
        } else {
            // Manejo del error en caso de que el usuario no se encuentre
            return json_encode([
                'success' => false,
                'message' => 'El usuario no se encuentra.'
            ]);
        }
    }

    public function create($userData)
    {
        // Crear un nuevo usuario
        $user = new User();
        foreach ($userData as $key => $value) {
            if (property_exists($user, $key)) {
                $user->$key = $value;
            }
        }
        $user->save();

        if ($user->id) {
            return json_encode([
                'success' => true,
                'message' => 'Usuario creado con éxito.',
                'user_id' => $user->id
            ]);
        } else {
            // Manejo del error en caso de que no se haya creado el usuario
            return json_encode([
                'success' => false,
                'message' => 'No se pudo crear el usuario.'
            ]);
        }
    }

    public function update($id, $userData)
    {
        // Actualizar un usuario existente
        $user = new User();
        $user = $user->find($id);
        foreach ($userData as $key => $value) {
            if (property_exists($user, $key)) {
                $user->$key = $value;
            }
        }
        $user->save();

        if ($user->id) {
            return json_encode([
                'success' => true,
                'message' => 'Usuario actualizado con éxito.'
            ]);
        } else {
            // Manejo del error en caso de que no se haya actualizado el usuario
            return json_encode([
                'success' => false,
                'message' => 'No se pudo actualizar el usuario.'
            ]);
        }
    }

    public function delete($id)
    {
        // Eliminar lógicamente (soft delete) un usuario existente
        $user = new User();
        $userFound = $user->find($id);

        if ($userFound->id) {
            $user->softDelete();
            // Aquí puedes redirigir al usuario o mostrar un mensaje de éxito
            return json_encode([
                'success' => true,
                'message' => 'Usuario eliminado con éxito.'
            ]);
        } else {
            // Manejo del error en caso de que el usuario no se encuentre
            return json_encode([
                'success' => false,
                'message' => 'El usuario no se encuentra.'
            ]);
        }
    }
}
