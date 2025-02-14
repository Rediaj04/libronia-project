<?php

namespace src\Middleware;

use Config\JWTAuth;
use Exception;

class AuthMiddleware {
    /**
     * Verifica si el usuario está autenticado mediante el token JWT.
     */
    public static function verificarToken() {
        if (!isset($_COOKIE['jwt_token'])) {
            header('Location: /login');
            exit;
        }

        $token = $_COOKIE['jwt_token'];
        $datos = JWTAuth::validarToken($token);

        if (!$datos) {
            // Token inválido o expirado
            setcookie('jwt_token', '', time() - 3600, '/'); // Eliminar la cookie
            header('Location: /login');
            exit;
        }

        // Almacenar el ID del usuario en la sesión (opcional)
        $_SESSION['user_id'] = $datos->usuario_id;
    }

    /**
     * Verifica si el usuario está autenticado mediante la sesión.
     * (Puedes eliminar este método si solo usas JWT).
     */
    public static function verificarSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
}