<?php
namespace Routing;

class Router {
    private $routes = [];

    /**
     * Registrar una nueva ruta en el enrutador
     * 
     * @param string $method El método HTTP (GET, POST, etc.)
     * @param string $route La ruta de la solicitud (ej: "/login")
     * @param callable $handler La función o método a ejecutar
     */
    public function addRoute($method, $route, $handler) {
        $this->routes[] = [
            'method' => $method,
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

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchRoute($route['route'], $uri)) {
                call_user_func($route['handler'], $this->extractParams($route['route'], $uri));
                return;
            }
        }

        // Respuesta 404 si no se encuentra la ruta
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
        $routePattern = preg_replace('/\{[a-zA-Z0-9_]+\}/', '([a-zA-Z0-9_-]+)', $route);
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
        $routePattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<\1>[a-zA-Z0-9_-]+)', $route);
        if (preg_match("#^$routePattern$#", $uri, $matches)) {
            foreach ($matches as $key => $value) {
                if (!is_int($key)) {
                    $params[$key] = $value;
                }
            }
        }
        return $params;
    }
}
