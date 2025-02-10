<?php

namespace src\Middleware;

use Config\JWTAuth;
use Exception;

class AuthMiddleware {
    public static function verificarSesion() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }

    public static function verificarToken() {
        if (!isset($_SERVER["HTTP_AUTHORIZATION"])) {
            http_response_code(401);
            echo json_encode(["error" => "Token no proporcionado"]);
            exit;
        }

        $token = str_replace("Bearer ", "", $_SERVER["HTTP_AUTHORIZATION"]);
        
        try {
            $datos = JWTAuth::validarToken($token);
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode(["error" => "Token inválido", "detalle" => $e->getMessage()]);
            exit;
        }

        return $datos;
    }
}