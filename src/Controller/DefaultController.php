<?php
namespace Controller;

use Config\TwigSetup;

class DefaultController {
    private $twig;

    public function __construct() {
        $this->twig = (new TwigSetup())->getTwig();
    }

    // Página principal
    public function home() {
        echo $this->twig->render('index.twig', ['name' => 'Libronia']);
    }

    // Página de administración
    public function admin() {
        if (!isset($_SESSION['user'])) {
            header('Location: /login');
            exit;
        }
        echo $this->twig->render('admin.twig', ['user' => $_SESSION['user']]);
    }

    // Página de login
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($_POST['username'] === 'admin' && $_POST['password'] === 'admin') {
                $_SESSION['user'] = 'admin';
                header('Location: /admin');
                exit;
            } else {
                echo $this->twig->render('login.twig', ['error' => 'Credenciales incorrectas.']);
                return;
            }
        }

        echo $this->twig->render('login.twig');
    }

    // Cerrar sesión
    public function logout() {
        session_destroy();
        header('Location: /');
        exit;
    }
}
