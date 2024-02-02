<?php

namespace App\Models;

use App\Config\Database;

class Session {
    public static function init() {
        session_start();
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }

    public static function get($key) {
        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        } else {
            return false;
        }
    }

    public static function destroy() {
        session_destroy();
    }

    public static function recordSessionStart($userId, $sessionToken, $ipAddress, $userAgent) {
        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("INSERT INTO session_logs (user_id, ip_address, user_agent, session_token) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $userId, $ipAddress, $userAgent, $sessionToken);
        $stmt->execute();
        $stmt->close();
    }

    public static function recordSessionEnd($userId, $sessionToken) {
        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("UPDATE session_logs SET session_end = NOW() WHERE user_id = ? AND session_token = ? AND session_end IS NULL");
        $stmt->bind_param("is", $userId, $sessionToken);
        $stmt->execute();
        $stmt->close();
    }
}