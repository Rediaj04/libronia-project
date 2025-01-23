<?php
// Conexión a la base de datos
require_once __DIR__ . '/../config/Database.php';

use Config\Database; // Importar la clase Database desde el namespace Config

try {
    $pdo = Database::getConnection(); // Usar el método estático para obtener la conexión
    
    // Categorías a insertar
    $categorias = [
        'Art',
        'Science',
        'History',
        'Technology',
        'Fiction',
        'Biography'
    ];

    foreach ($categorias as $categoria) {
        $sql = "INSERT IGNORE INTO categorias (nombre_categoria) VALUES (:nombre)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':nombre' => $categoria]);
    }

    echo "Categorías insertadas con éxito.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}