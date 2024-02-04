<?php

namespace App\Controllers;

ini_set('log_errors', 1);
ini_set('error_log', './errors.log');
error_reporting(E_ALL);

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
        Session::init(); // Asegurarse de que la sesión esté iniciada.
    
        $userId = Session::get('user_id');
        $sessionToken = Session::get('session_token');
    
        if ($userId && $sessionToken) {
            Session::recordSessionEnd($userId, $sessionToken);
            Session::destroy();
            return true;
        } else {
            error_log("Error al cerrar sesión: No se pudo obtener userId o sessionToken.");
            return false;
        }
    }
    
}
