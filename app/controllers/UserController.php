<?php

namespace App\Controllers;

use App\Models\User;

class UserController
{
    public function index()
    {
        // Mostrar una lista de usuarios
    }

    public function show($id)
    {
        // Mostrar un usuario específico
        $user = new User();
        $user = $user->find($id);
        // Cargar vista con datos de usuario
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
        // Redirigir o mostrar resultado
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
        // Redirigir o mostrar resultado
    }

    public function delete($id)
    {
        // Eliminar lógicamente (soft delete) un usuario existente
        $user = new User();
        $userFound = $user->find($id);

        if ($userFound->id) {
            $user->softDelete();
            // Aquí puedes redirigir al usuario o mostrar un mensaje de éxito
            echo "El usuario con ID $id ha sido desactivado exitosamente.";
        } else {
            // Manejo del error en caso de que el usuario no se encuentre
            echo "El usuario con ID $id no se encuentra.";
        }
    }
}
