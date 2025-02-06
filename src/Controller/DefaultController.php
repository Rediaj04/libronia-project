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

    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function home() {
        try {
            $db = (new Database())->connect();
            $query = "SELECT DISTINCT c.nombre_categoria FROM categorias c ORDER BY c.nombre_categoria;";
            $stmt = $db->query($query);
            $categorias = $stmt->fetchAll(PDO::FETCH_COLUMN);

            echo $this->twig->render('index.twig', ['categorias' => $categorias]);
        } catch (PDOException $e) {
            echo "Error al cargar las categorías: " . $e->getMessage();
            error_log("Error en home(): " . $e->getMessage());
        }
    }

    public function admin() {
        \Middleware\AuthMiddleware::verificarSesion();
        echo $this->twig->render('admin.twig', ['user' => $_SESSION['user_id']]);
    }

    public function login() {
        $this->initSession();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
            $password = $_POST['password'];

            try {
                $db = (new Database())->connect();
                $stmt = $db->prepare("SELECT id, contrasena FROM usuarios WHERE correo = :username");
                $stmt->bindParam(':username', $username, PDO::PARAM_STR);
                $stmt->execute();

                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['contrasena'])) {
                    $_SESSION['user_id'] = $user['id'];
                    header('Location: /admin');
                    exit;
                } else {
                    echo $this->twig->render('login.twig', ['error' => 'Credenciales incorrectas.']);
                    return;
                }
            } catch (PDOException $e) {
                echo $this->twig->render('login.twig', ['error' => 'Error en el sistema. Intenta nuevamente más tarde.']);
                error_log("Error en login(): " . $e->getMessage());
                return;
            }
        }

        echo $this->twig->render('login.twig');
    }

    public function logout() {
        $this->initSession();
        session_destroy();
        header('Location: /login');
        exit;
    }

    public function charts() {
        echo $this->twig->render('charts.twig');
    }

    public function categoria($params) {
        try {
            $db = (new Database())->connect();
            $nombre_categoria = urldecode($params['nombre_categoria']);

            $checkCategoryQuery = "SELECT id FROM categorias WHERE nombre_categoria = :nombre_categoria";
            $checkStmt = $db->prepare($checkCategoryQuery);
            $checkStmt->bindParam(':nombre_categoria', $nombre_categoria, PDO::PARAM_STR);
            $checkStmt->execute();

            if ($checkStmt->rowCount() === 0) {
                http_response_code(404);
                echo $this->twig->render('404.twig', ['mensaje' => 'Categoría no encontrada']);
                return;
            }

            $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
            $offset = ($page - 1) * $this->itemsPerPage;

            $query = "
                SELECT l.id, l.titulo, l.imagen_url
                FROM libros l
                INNER JOIN categorias c ON l.categoria_id = c.id
                WHERE c.nombre_categoria = :nombre_categoria
                LIMIT :offset, :items_per_page
            ";

            $stmt = $db->prepare($query);
            $stmt->bindParam(':nombre_categoria', $nombre_categoria, PDO::PARAM_STR);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->bindValue(':items_per_page', $this->itemsPerPage, PDO::PARAM_INT);
            $stmt->execute();

            $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

            echo $this->twig->render('categoria.twig', [
                'categoria' => $nombre_categoria,
                'libros' => $libros,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'totalLibros' => $totalLibros
            ]);
        } catch (PDOException $e) {
            echo "Error al cargar los libros de la categoría: " . $e->getMessage();
            error_log("Error en categoria(): " . $e->getMessage());
        }
    }

    public function detalleLibro($params) {
        try {
            $db = (new Database())->connect();
            $libroId = (int) $params['id'];
            error_log("ID recibido en detalleLibro: " . $libroId);

            $query = "
        SELECT 
        l.id, 
        l.titulo, 
        l.descripcion, 
        l.imagen_url, 
        l.fecha_publicacion, 
        l.formato, 
        l.num_paginas, 
        l.num_calificaciones, 
        l.num_resenas, 
        l.libro_url,
        a.nombre_autor, 
        c.nombre_categoria
        FROM libros l
        LEFT JOIN autores a ON l.autor_id = a.id
        LEFT JOIN categorias c ON l.categoria_id = c.id
        WHERE l.id = :libroId
        ";        

            $stmt = $db->prepare($query);
            $stmt->bindParam(':libroId', $libroId, PDO::PARAM_INT);
            $stmt->execute();

            $libro = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$libro) {
                http_response_code(404);
                echo $this->twig->render('404.twig', ['mensaje' => 'Libro no encontrado']);
                return;
            }

            echo $this->twig->render('libro.twig', ['libro' => $libro]);
        } catch (PDOException $e) {
            echo "Error al cargar el detalle del libro: " . $e->getMessage();
            error_log("Error en detalleLibro(): " . $e->getMessage());
        }
    }

    public function buscarLibros($params) {
        $query = $_GET['q'] ?? '';
        
        $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
        $itemsPerPage = 10;
        $offset = ($page - 1) * $itemsPerPage;
        
        $db = Database::getConnection();
        
        $sql = "SELECT l.id, l.titulo, a.nombre_autor AS autor, l.fecha_publicacion, l.imagen_url 
                FROM libros l 
                LEFT JOIN autores a ON l.autor_id = a.id 
                WHERE l.titulo LIKE :query OR a.nombre_autor LIKE :query
                LIMIT :offset, :itemsPerPage";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
        $stmt->execute();
        $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sqlCount = "SELECT COUNT(*) FROM libros l 
                     LEFT JOIN autores a ON l.autor_id = a.id 
                     WHERE l.titulo LIKE :query OR a.nombre_autor LIKE :query";
        $stmtCount = $db->prepare($sqlCount);
        $stmtCount->bindValue(':query', "%$query%", PDO::PARAM_STR);
        $stmtCount->execute();
        $totalLibros = $stmtCount->fetchColumn();
        $totalPages = ceil($totalLibros / $itemsPerPage);
        
        echo $this->twig->render('buscar.twig', [
            'query'       => $query,
            'libros'      => $libros,
            'currentPage' => $page,
            'totalPages'  => $totalPages,
            'totalLibros' => $totalLibros
        ]);
    }    
}

