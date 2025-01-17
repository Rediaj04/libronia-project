<?php
namespace Config;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class TwigSetup {
    public function getTwig() {
        // Ruta correcta basada en la ubicación del archivo actual
        $loader = new FilesystemLoader(__DIR__ . '/../templates');
        return new Environment($loader);
    }
}
