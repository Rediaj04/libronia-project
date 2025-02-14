<?php
namespace src\Routing;

use src\Controller\DefaultController;
use src\Middleware\AuthMiddleware;

class Router {
    private $routes = [];

    /**
     * Registrar una nueva ruta en el enrutador
     * 
     * @param string $method El método HTTP (GET, POST, etc.)
     * @param string $route La ruta de la solicitud (ej: "/login")
     * @param callable $handler La función o método a ejecutar
     * @param callable|null $middleware Middleware a ejecutar antes del handler
     */
    public function addRoute($method, $route, $handler, $middleware = null) {
        $this->routes[] = [
            'method' => $method,
            'middleware' => $middleware, // Middleware asociado a la ruta
            'route' => $this->normalizeRoute($route),
            'handler' => $handler
        ];
    }

    /**
     * Manejar la solicitud entrante
     * 
     * @param string $method El método HTTP usado en la solicitud
     * @param string $uri La URI solicitada
     */
    public function handleRequest($method, $uri) {
        $uri = $this->normalizeRoute($uri);
        error_log("Manejando petición: " . $method . " " . $uri);

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchRoute($route['route'], $uri)) {
                error_log("Ruta encontrada: " . $route['route']);

                // Ejecutar middleware si está definido
                if ($route['middleware']) {
                    call_user_func($route['middleware']);
                }

                // Extraer parámetros de la ruta
                $params = $this->extractParams($route['route'], $uri);

                // Ejecutar el handler de la ruta
                call_user_func($route['handler'], $params);
                return;
            }
        }

        // Si no se encuentra la ruta
        http_response_code(404);
        echo "Página no encontrada.";
    }

    /**
     * Normalizar una ruta eliminando barras finales
     * 
     * @param string $route La ruta a normalizar
     * @return string La ruta normalizada
     */
    private function normalizeRoute($route) {
        return rtrim($route, '/') ?: '/';
    }

    /**
     * Verificar si una ruta coincide con una URI
     * 
     * @param string $route La ruta registrada
     * @param string $uri La URI solicitada
     * @return bool Verdadero si coinciden, falso de lo contrario
     */
    private function matchRoute($route, $uri) {
        $routePattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_%20-]+)', $route);
        error_log("Comparando URI: " . $uri . " con patrón: " . $routePattern);
        return preg_match("#^$routePattern$#", $uri);
    }

    /**
     * Extraer parámetros dinámicos de la URI
     * 
     * @param string $route La ruta registrada con parámetros
     * @param string $uri La URI solicitada
     * @return array Parámetros extraídos
     */
    private function extractParams($route, $uri) {
        $params = [];
        $routePattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_%20-]+)', $route);
        if (preg_match("#^$routePattern$#", $uri, $matches)) {
            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $params[$key] = $value;
                }
            }
        }
        error_log("Extrayendo parámetros de la ruta: " . $route . " con URI: " . $uri);
        return $params;
    }
}

// Crear una instancia del enrutador
$router = new Router();

// Rutas públicas
$router->addRoute('GET', '/', [new DefaultController(), 'home']);
$router->addRoute('GET', '/login', [new DefaultController(), 'login']);
$router->addRoute('POST', '/login', [new DefaultController(), 'login']);
$router->addRoute('GET', '/logout', [new DefaultController(), 'logout']);
$router->addRoute('GET', '/charts', [new DefaultController(), 'charts']);
$router->addRoute('GET', '/categoria/{nombre_categoria}', [new DefaultController(), 'categoria']);
$router->addRoute('GET', '/libro/{id}', [new DefaultController(), 'detalleLibro']);
$router->addRoute('GET', '/buscar', [new DefaultController(), 'buscarLibros']);

// Rutas protegidas con JWT
$router->addRoute('GET', '/admin', [new DefaultController(), 'admin'], [new AuthMiddleware(), 'verificarToken']);
$router->addRoute('GET', '/editar/{id}', [new DefaultController(), 'editarLibro'], [new AuthMiddleware(), 'verificarToken']);
$router->addRoute('POST', '/editar/{id}', [new DefaultController(), 'editarLibro'], [new AuthMiddleware(), 'verificarToken']);
$router->addRoute('GET', '/eliminar/{id}', [new DefaultController(), 'eliminarLibro'], [new AuthMiddleware(), 'verificarToken']);

// Manejo de la solicitud
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