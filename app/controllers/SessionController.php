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
            
            // Registrar el inicio de sesión
            $sessionToken = bin2hex(random_bytes(32)); // Genera un token seguro
            $ipAddress = $_SERVER['REMOTE_ADDR']; // IP del cliente
            $userAgent = $_SERVER['HTTP_USER_AGENT']; // Agente de usuario del cliente

            Session::set('session_token', $sessionToken);
            Session::recordSessionStart($authenticatedUser->id, $sessionToken, $ipAddress, $userAgent);

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
        $userId = Session::get('user_id');
        $sessionToken = Session::get('session_token');

        Session::recordSessionEnd($userId, $sessionToken);
        Session::destroy();
        // Devolver true/éxito
        return true;
    }
}
