<?php
class Database {
    // Parámetros de conexión
    private $host = 'localhost';   // Host de la base de datos
    private $dbname = 'libronia';  // Nombre de la base de datos
    private $username = 'libronia'; // Usuario para la base de datos
    private $password = 'usuario';  // Contraseña del usuario

    // Propiedad para almacenar la conexión PDO
    private $conn;

    /**
     * Función para establecer la conexión a la base de datos utilizando PDO
     * @return PDO Objeto de conexión PDO
     */
    public function connect() {
        // Si la conexión ya está establecida, simplemente devolverla
        if ($this->conn == null) {
            try {
                // Establece la conexión a la base de datos
                $this->conn = new PDO("mysql:host=$this->host;dbname=$this->dbname", $this->username, $this->password);
                
                // Configura el manejo de errores
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $e) {
                // Si ocurre un error, muestra el mensaje de error
                echo "Connection failed: " . $e->getMessage();
            }
        }
        
        return $this->conn;  // Devuelve el objeto de conexión
    }

    /**
     * Método para cerrar la conexión (no estrictamente necesario, pero útil)
     */
    public function disconnect() {
        $this->conn = null; // Se establece la conexión a null para cerrar la conexión
    }
}
?>
