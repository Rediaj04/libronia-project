<?php
namespace Config;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

class TwigSetup {
    public function getTwig() {
        $loader = new FilesystemLoader(__DIR__ . '/../templates');
        
        // Crear la instancia de Twig
        $twig = new Environment($loader);

        // Registrar la función `_()` para usar gettext en Twig
        $twig->addFunction(new TwigFunction('_', function ($message) {
            $translated = gettext($message);
            error_log("Twig translation attempt: '$message' => '$translated'");
            return $translated;
        }));

        return $twig;
    }
}