<?php

namespace Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTAuth {
    private static $secret_key = "holaxd123"; 
    private static $algoritmo = "HS256";

    /**
     * Generar un token JWT
     */
    public static function generarToken($usuario_id) {
        $payload = [
            "iat" => time(),              // Tiempo actual
            "exp" => time() + 3600,       // Expira en 1 hora
            "usuario_id" => $usuario_id   // ID del usuario autenticado
        ];
        
        return JWT::encode($payload, self::$secret_key, self::$algoritmo);
    }

    /**
     * Validar un token JWT
     */
    public static function validarToken($token) {
        try {
            return JWT::decode($token, new Key(self::$secret_key, self::$algoritmo));
        } catch (Exception $e) {
            return null; // Token inválido
        }
    }
}