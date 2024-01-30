<?php

namespace App\Controllers;

use App\Models\Session;
use App\Models\User;

class SessionController
{
    public function login($username, $password)
    {
        // Iniciar la sesión
        Session::init();

        // Aquí deberías verificar las credenciales del usuario
        $user = new User();
        $authenticatedUser = $user->verifyCredentials($username, $password);

        if ($authenticatedUser) {
            // Si las credenciales son correctas, establecer datos de sesión
            Session::set('user_id', $authenticatedUser->id);
            Session::set('username', $authenticatedUser->username);
            // Devolver true/éxito
            return true;
        } else {
            // Si las credenciales son incorrectas, false/error
            return false;
        }
    }

    public function verify()
    {
        // Verificar si hay una sesión de usuario activa
        Session::init();
        if (Session::get('user_id')) {
            // La sesión está activa
            return true;
        } else {
            // No hay sesión activa
            return false;
        }
    }

    public function logout()
    {
        // Cerrar la sesión del usuario
        Session::destroy();
        // Devolver true/éxito
        return true;
    }
}
