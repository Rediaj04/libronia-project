<?php
namespace Routing;

class Router {
    private $routes = [];

    public function addRoute($method, $route, $handler) {
        $this->routes[] = ['method' => $method, 'route' => $route, 'handler' => $handler];
    }

    public function handleRequest($method, $uri) {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['route'] === $uri) {
                call_user_func($route['handler']);
                return;
            }
        }
        echo "Ruta no encontrada.";
    }
}
