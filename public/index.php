<?php

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

use src\Routing\Router;
use src\Controller\DefaultController;
use src\Controller\AuthController;
use src\Controller\AdminController;
use src\Middleware\AuthMiddleware;

$router = new Router();

// Usar addRoute en lugar de map
$router->addRoute('GET', '/', [new DefaultController(), 'home']);
$router->addRoute('GET', '/login', [new DefaultController(), 'login']);
$router->addRoute('POST', '/login', [new DefaultController(), 'login']);
$router->addRoute('GET', '/logout', [new DefaultController(), 'logout']);
$router->addRoute('GET', '/charts', [new DefaultController(), 'charts']);
$router->addRoute('GET', '/categoria/{nombre_categoria}', [new DefaultController(), 'categoria']);
$router->addRoute('GET', '/libro/{id}', [new DefaultController(), 'detalleLibro']);
$router->addRoute('GET', '/buscar', [new DefaultController(), 'buscarLibros']);
$router->addRoute('POST', '/api/login', [AuthController::class, 'login']);
$router->addRoute('GET', '/admin', [new DefaultController(), 'admin'], 'Middleware\AuthMiddleware::verificarSesion');

// Aquí también debes usar addRoute en lugar de map
$router->addRoute('GET', '/admin', [DefaultController::class, 'admin']);
$router->addRoute('GET', '/editar/{id}', [new DefaultController(), 'editarLibro']);
$router->addRoute('POST', '/editar/{id}', [new DefaultController(), 'editarLibro']);
$router->addRoute('GET', '/eliminar/{id}', [new DefaultController(), 'eliminarLibro']);

try {
    $router->handleRequest(
        $_SERVER['REQUEST_METHOD'], 
        parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
    );
} catch (Exception $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
    error_log($e->getMessage());
}
