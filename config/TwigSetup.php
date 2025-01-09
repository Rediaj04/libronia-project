<?php
namespace Config;

use Twig\Loader\FilesystemLoader;
use Twig\Environment;

class TwigSetup {
    public function getTwig() {
        $loader = new FilesystemLoader('../templates');
        return new Environment($loader);
    }
}
