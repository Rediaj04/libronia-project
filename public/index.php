<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Habilitar la visualización de errores para desarrollo
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar la sesión
session_start();

// Cargar el autoloader de Composer
require_once '../vendor/autoload.php';

use Routing\Router;
use Controller\DefaultController;

// Crear una instancia del enrutador
$router = new Router();

// Registrar rutas
$router->addRoute('GET', '/', [new DefaultController(), 'home']);      // Página principal
$router->addRoute('GET', '/admin', [new DefaultController(), 'admin']); // Panel de administración
$router->addRoute('GET', '/login', [new DefaultController(), 'login']); // Página de login
$router->addRoute('POST', '/login', [new DefaultController(), 'login']); // Manejo de formulario de login
$router->addRoute('GET', '/logout', [new DefaultController(), 'logout']); // Cerrar sesión

// Manejar la solicitud entrante
try {
    $router->handleRequest(
        $_SERVER['REQUEST_METHOD'], 
        parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
    );
} catch (Exception $e) {
    // Manejar errores globales y devolver un mensaje adecuado
    http_response_code(500);
    echo "Ha ocurrido un error en el servidor.";
}
