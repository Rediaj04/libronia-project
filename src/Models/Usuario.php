<?php

namespace Models;

use Config\Database;
use PDO;
use PDOException;

class Usuario {
    private $db;

    public function __construct() {
        $this->db = (new Database())->connect();
    }

    /**
     * Buscar usuario por email
     */
    public function obtenerPorEmail($email) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
}
