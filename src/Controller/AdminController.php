<?php

namespace src\Controller;

use Middleware\AuthMiddleware;

class AdminController {
    public function panel() {
        AuthMiddleware::verificarToken();
        echo "Bienvenido al panel de administración.";
    }
}
