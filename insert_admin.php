<?php
require_once 'config/db.php'; // Incluye la configuración para conectar a la base de datos

/**
 * Este script se utiliza para insertar un usuario administrador predeterminado en la tabla `usuarios`.
 * Usuario: admin
 * Contraseña: admin123
 * Email: admin@libronia.local
 * Ejecutar este archivo una sola vez para configurar el usuario inicial.
 */

try {
    // Conectar a la base de datos
    $db = (new Database())->connect();

    // Datos del usuario administrador
    $username = 'admin'; // Ahora 'nombre'
    $email = 'admin@libronia.local'; // Ahora 'correo'
    $password = 'admin123';

    // Crear el hash de la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar usuario en la base de datos
    $stmt = $db->prepare("
        INSERT INTO usuarios (nombre, correo, contrasena) 
        VALUES (:nombre, :correo, :contrasena)
    ");
    $stmt->bindParam(':nombre', $username);
    $stmt->bindParam(':correo', $email);
    $stmt->bindParam(':contrasena', $password_hash);
    $stmt->execute();

    echo "Usuario administrador creado correctamente.";
} catch (Exception $e) {
    echo "Error al crear el usuario: " . $e->getMessage();
}
