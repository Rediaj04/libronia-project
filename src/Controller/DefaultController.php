<?php

namespace Controller;

use Config\Database;
use Config\TwigSetup;
use PDO;
use PDOException;

class DefaultController {
    private $twig;
    private $itemsPerPage = 10;

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
     * Página principal: muestra las categorías.
     */
    public function home() {
        try {
            // Conectar a la base de datos
            $db = (new Database())->connect();

            // Consulta para obtener categorías
            $query = "
                SELECT DISTINCT c.nombre_categoria
                FROM categorias c
                ORDER BY c.nombre_categoria;
            ";

            // Ejecutar consulta
            $stmt = $db->query($query);

            // Obtener categorías
            $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Renderizar la vista con las categorías
            echo $this->twig->render('index.twig', [
                'categorias' => $categorias
            ]);
        } catch (PDOException $e) {
            // Manejar errores de conexión o consulta
            echo "Error al cargar los datos: " . $e->getMessage();
            error_log("Error al cargar categorías: " . $e->getMessage());
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
        echo $this->twig->render('charts.twig');
    }

    /**
     * Página de categoría: muestra los libros de una categoría específica
     */
    public function categoria($params) {
        try {
            $db = (new Database())->connect();
    
            $nombre_categoria = urldecode($params['nombre_categoria']);
            
            // Verificar si la categoría existe
            $checkCategoryQuery = "SELECT id FROM categorias WHERE nombre_categoria = :nombre_categoria";
            $checkStmt = $db->prepare($checkCategoryQuery);
            $checkStmt->bindParam(':nombre_categoria', $nombre_categoria, PDO::PARAM_STR);
            $checkStmt->execute();
    
            if ($checkStmt->rowCount() === 0) {
                // Categoría no encontrada
                http_response_code(404);
                echo $this->twig->render('404.twig', ['mensaje' => 'Categoría no encontrada']);
                return;
            }
    
            // Paginación
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $this->itemsPerPage;
    
            // Consulta para obtener los libros de la categoría con paginación
            $query = "
                SELECT l.titulo, l.imagen_url
                FROM libros l
                INNER JOIN categorias c ON l.categoria_id = c.id
                WHERE c.nombre_categoria = :nombre_categoria
                LIMIT :offset, :items_per_page
            ";
    
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre_categoria', $nombre_categoria, PDO::PARAM_STR);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindParam(':items_per_page', $this->itemsPerPage, PDO::PARAM_INT);
            $stmt->execute();
    
            $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            // Obtener el total de libros para la paginación
            $countQuery = "
                SELECT COUNT(*) as total
                FROM libros l
                INNER JOIN categorias c ON l.categoria_id = c.id
                WHERE c.nombre_categoria = :nombre_categoria
            ";
            $countStmt = $db->prepare($countQuery);
            $countStmt->bindParam(':nombre_categoria', $nombre_categoria, PDO::PARAM_STR);
            $countStmt->execute();
            $totalLibros = $countStmt->fetchColumn();
    
            $totalPages = ceil($totalLibros / $this->itemsPerPage);
    
            // Renderizar la vista de categoría
            echo $this->twig->render('categoria.twig', [
                'categoria' => $nombre_categoria,
                'libros' => $libros,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalLibros' => $totalLibros
            ]);
        } catch (PDOException $e) {
            echo "Error al cargar los libros de la categoría: " . $e->getMessage();
            error_log("Error en categoría: " . $e->getMessage());
        }
    }
}