<?php

namespace Config;

use PDO;
use PDOException;

class Database {
    // Parámetros de conexión
    private $host = 'localhost';      // Host de la base de datos
    private $dbname = 'libronia';     // Nombre de la base de datos
    private $username = 'libronia';   // Usuario de la base de datos
    private $password = 'usuario';    // Contraseña del usuario
    private $charset = 'utf8mb4';     // Conjunto de caracteres

    // Propiedad para almacenar la conexión PDO
    private $conn = null;

    /**
     * Constructor opcional para configurar parámetros de conexión dinámicamente
     */
    public function __construct($host = null, $dbname = null, $username = null, $password = null) {
        $this->host = $host ?? $this->host;
        $this->dbname = $dbname ?? $this->dbname;
        $this->username = $username ?? $this->username;
        $this->password = $password ?? $this->password;
    }

    /**
     * Establece la conexión a la base de datos utilizando PDO
     * @return PDO Objeto de conexión PDO
     * @throws PDOException Si ocurre un error al conectar
     */
    public function connect() {
        if ($this->conn === null) {
            try {
                // Establece la conexión a la base de datos
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
                $this->conn = new PDO($dsn, $this->username, $this->password);

                // Configura el manejo de errores
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                // Registra el error y lanza una excepción personalizada
                error_log("Error de conexión a la base de datos: " . $e->getMessage());
                throw new PDOException("No se pudo conectar a la base de datos. Intente más tarde.");
            }
        }

        return $this->conn;
    }

    /**
     * Cierra la conexión a la base de datos
     */
    public function disconnect() {
        $this->conn = null;
    }

    /**
     * Método estático para obtener una conexión rápida
     * @return PDO
     * @throws PDOException
     */
    public static function getConnection() {
        $instance = new self();
        return $instance->connect();
    }
}
