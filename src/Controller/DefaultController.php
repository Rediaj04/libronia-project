<?php

namespace Controller;

use Config\Database;
use Config\TwigSetup;
use PDO;
use PDOException;

class DefaultController {
    private $twig;

    public function __construct() {
        $this->twig = (new TwigSetup())->getTwig();
    }

    /**
     * Inicializar sesión si no está activa.
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Página principal: muestra las categorías y los autores.
     */
    public function home() {
        try {
            // Conectar a la base de datos
            $db = (new Database())->connect();

            // Consulta para obtener categorías y sus autores
            $query = "
                SELECT c.nombre_categoria, a.nombre_autor
                FROM categorias c
                LEFT JOIN libros l ON c.id = l.categoria_id
                LEFT JOIN autores a ON l.autor_id = a.id
                ORDER BY c.nombre_categoria, a.nombre_autor;
            ";

            // Ejecutar consulta
            $stmt = $db->query($query);

            // Agrupar resultados por categorías
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $categorias = [];
            foreach ($result as $row) {
                $categorias[$row['nombre_categoria']][] = ['autor_nombre' => $row['nombre_autor']];
            }

            // Renderizar la vista con las categorías y autores
            echo $this->twig->render('index.twig', [
                'categorias' => $categorias
            ]);
        } catch (PDOException $e) {
            // Manejar errores de conexión o consulta
            echo "Error al cargar los datos: " . $e->getMessage();
            error_log("Error al cargar categorías y autores: " . $e->getMessage());
        }
    }

    /**
     * Página de administración
     */
    public function admin() {
        $this->initSession();

        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        echo $this->twig->render('admin.twig', ['user' => $_SESSION['user_id']]);
    }

    /**
     * Página de login
     */
    public function login() {
        $this->initSession();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $password = $_POST['password'];

            try {
                // Conectar a la base de datos
                $db = (new Database())->connect();

                // Consultar el usuario usando la columna correcta
                $stmt = $db->prepare("SELECT id, contrasena FROM usuarios WHERE correo = :username");
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->execute();

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['contrasena'])) {
                    session_regenerate_id(true); // Prevenir ataques de sesión
                    $_SESSION['user_id'] = $user['id'];
                    header('Location: /admin');
                    exit;
                } else {
                    echo $this->twig->render('login.twig', ['error' => 'Credenciales incorrectas.']);
                    return;
                }
            } catch (PDOException $e) {
                echo $this->twig->render('login.twig', ['error' => 'Error en el sistema. Intenta nuevamente más tarde.']);
                error_log("Error de conexión o consulta: " . $e->getMessage());
                return;
            }
        }

        echo $this->twig->render('login.twig');
    }

    /**
     * Cerrar sesión
     */
    public function logout() {
        $this->initSession();
        session_destroy();
        header('Location: /login');
        exit;
    }

    /**
     * Página de gráficos (Charts)
     */
    public function charts() {
        echo "Grafico aquí";
        echo $this->twig->render('charts.twig');
    }
}