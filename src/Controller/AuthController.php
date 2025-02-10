<?php

namespace src\Controller;

use Models\Usuario;
use Config\JWTAuth;

class AuthController {
    /**
     * Manejar la autenticación del usuario
     */
    public function login() {
        header("Content-Type: application/json");
        
        // Obtener datos del cuerpo de la solicitud
        $datos = json_decode(file_get_contents("php://input"), true);
        $email = $datos["email"] ?? "";
        $password = $datos["password"] ?? "";

        if (empty($email) || empty($password)) {
            echo json_encode(["error" => "Email y contraseña requeridos"]);
            http_response_code(400);
            return;
        }

        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->obtenerPorEmail($email);

        if (!$usuario || !password_verify($password, $usuario["password"])) {
            echo json_encode(["error" => "Credenciales inválidas"]);
            http_response_code(401);
            return;
        }

        $token = JWTAuth::generarToken($usuario["id"]);

        echo json_encode(["token" => $token]);
    }
}
