<?php

namespace src\Controller;

use Config\Database;
use Config\TwigSetup;
use PDO;
use PDOException;
use src\Middleware\AuthMiddleware;

class DefaultController {
    private $twig;
    private $itemsPerPage = 24;

    public function __construct() {
        $this->twig = (new TwigSetup())->getTwig();
        $this->initSession(); // Iniciar sesión si no está activa
    
        // Obtener el usuario si está logueado
        if (isset($_SESSION['user_id'])) {
            $db = (new Database())->connect();
            $userId = $_SESSION['user_id'];
            $stmt = $db->prepare("SELECT nombre FROM usuarios WHERE id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $this->user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $this->user = null; // No hay usuario logueado
        }
    
        // Pasar el usuario a todas las vistas
        $this->twig->addGlobal('user', $this->user);
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
        AuthMiddleware::verificarSesion();
        $this->initSession(); // Asegúrate de que la sesión esté iniciada
    
        $db = (new Database())->connect();
    
        // Obtener el nombre del usuario desde la base de datos
        $userId = $_SESSION['user_id'];
        $stmt = $db->prepare("SELECT nombre FROM usuarios WHERE id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Parámetros de paginación
        $itemsPerPage = 30; // Mostrar 30 libros por página
        $page = isset($_GET['page']) ? max((int)$_GET['page'], 1) : 1;
        $offset = ($page - 1) * $itemsPerPage;
    
        // Parámetros de búsqueda
        $searchQuery = $_GET['q'] ?? '';
        $searchCondition = '';
        if (!empty($searchQuery)) {
            $searchCondition = "WHERE l.titulo LIKE :searchQuery OR a.nombre_autor LIKE :searchQuery OR c.nombre_categoria LIKE :searchQuery";
        }
    
        // Consulta para obtener los libros con paginación y búsqueda
        $query = "
            SELECT l.id, l.titulo, a.nombre_autor AS autor, c.nombre_categoria AS categoria
            FROM libros l
            LEFT JOIN autores a ON l.autor_id = a.id
            LEFT JOIN categorias c ON l.categoria_id = c.id
            $searchCondition
            LIMIT :offset, :itemsPerPage
        ";
    
        $stmt = $db->prepare($query);
        if (!empty($searchQuery)) {
            $searchParam = "%$searchQuery%";
            $stmt->bindParam(':searchQuery', $searchParam, PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':itemsPerPage', $itemsPerPage, PDO::PARAM_INT);
        $stmt->execute();
        $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Consulta para contar el total de libros (para la paginación)
        $countQuery = "
            SELECT COUNT(*) as total
            FROM libros l
            LEFT JOIN autores a ON l.autor_id = a.id
            LEFT JOIN categorias c ON l.categoria_id = c.id
            $searchCondition
        ";
        $countStmt = $db->prepare($countQuery);
        if (!empty($searchQuery)) {
            $countStmt->bindParam(':searchQuery', $searchParam, PDO::PARAM_STR);
        }
        $countStmt->execute();
        $totalLibros = $countStmt->fetchColumn();
    
        // Calcular el total de páginas
        $totalPages = ceil($totalLibros / $itemsPerPage);
    
        // Renderizar la vista con los datos
        echo $this->twig->render('admin.twig', [
            'libros' => $libros,
            'user' => $user, // Pasar el nombre del usuario a la vista
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'searchQuery' => $searchQuery,
        ]);
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
        $itemsPerPage = 24;
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

    // Editar libro
    public function editarLibro($params) {
        $id = (int)$params['id'];
        $db = (new Database())->connect();
    
        // Si el método es POST, actualizar el libro
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Recuperar datos del formulario
            $titulo = $_POST['titulo'];
            $autorNombre = $_POST['autor'];
            $categoriaId = $_POST['categoria'];
            $descripcion = $_POST['descripcion'];
            $fecha_publicacion = $_POST['fecha_publicacion'];
            $formato = $_POST['formato'];
            $num_paginas = $_POST['num_paginas'];
            $num_calificaciones = $_POST['num_calificaciones'];
            $num_resenas = $_POST['num_resenas'];
    
            // Manejo de la imagen
            $imagen_url = null;
            if (isset($_FILES['nueva_imagen']) && $_FILES['nueva_imagen']['error'] === UPLOAD_ERR_OK) {
                $imagen = $_FILES['nueva_imagen'];
                $nombre_imagen = uniqid() . '_' . basename($imagen['name']);
                $ruta_imagen = "public/uploads/" . $nombre_imagen;
    
                if (move_uploaded_file($imagen['tmp_name'], $ruta_imagen)) {
                    $imagen_url = "/uploads/" . $nombre_imagen;
                } else {
                    $mensaje = "Error al subir la imagen.";
                    $tipoMensaje = "error";
                }
            }
    
            try {
                // Verificar si el autor existe, si no, crearlo
                $stmtAutor = $db->prepare("SELECT id FROM autores WHERE nombre_autor = :nombre_autor");
                $stmtAutor->bindParam(':nombre_autor', $autorNombre);
                $stmtAutor->execute();
                $autor = $stmtAutor->fetch(PDO::FETCH_ASSOC);
    
                if (!$autor) {
                    $stmtInsertAutor = $db->prepare("INSERT INTO autores (nombre_autor) VALUES (:nombre_autor)");
                    $stmtInsertAutor->bindParam(':nombre_autor', $autorNombre);
                    $stmtInsertAutor->execute();
                    $autor_id = $db->lastInsertId();
                } else {
                    $autor_id = $autor['id'];
                }
    
                // Actualizar libro con los nuevos datos
                $sql = "UPDATE libros SET 
                        titulo = :titulo, 
                        autor_id = :autor_id, 
                        categoria_id = :categoria_id, 
                        descripcion = :descripcion, 
                        fecha_publicacion = :fecha_publicacion, 
                        formato = :formato, 
                        num_paginas = :num_paginas, 
                        num_calificaciones = :num_calificaciones, 
                        num_resenas = :num_resenas";
    
                if ($imagen_url) {
                    $sql .= ", imagen_url = :imagen_url";
                }
    
                $sql .= " WHERE id = :id";
    
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':titulo', $titulo);
                $stmt->bindParam(':autor_id', $autor_id);
                $stmt->bindParam(':categoria_id', $categoriaId);
                $stmt->bindParam(':descripcion', $descripcion);
                $stmt->bindParam(':fecha_publicacion', $fecha_publicacion);
                $stmt->bindParam(':formato', $formato);
                $stmt->bindParam(':num_paginas', $num_paginas);
                $stmt->bindParam(':num_calificaciones', $num_calificaciones);
                $stmt->bindParam(':num_resenas', $num_resenas);
                if ($imagen_url) {
                    $stmt->bindParam(':imagen_url', $imagen_url);
                }
                $stmt->bindParam(':id', $id);
    
                if ($stmt->execute()) {
                    $mensaje = "El libro se ha actualizado correctamente.";
                    $tipoMensaje = "success";
                } else {
                    $mensaje = "Error al actualizar el libro.";
                    $tipoMensaje = "error";
                }
            } catch (PDOException $e) {
                $mensaje = "Error al actualizar el libro: " . $e->getMessage();
                $tipoMensaje = "error";
            }
        }
    
        try {
            // Obtener el libro con el id correspondiente
            $stmt = $db->prepare("SELECT l.*, a.nombre_autor AS autor, c.nombre_categoria AS categoria 
                                  FROM libros l 
                                  LEFT JOIN autores a ON l.autor_id = a.id 
                                  LEFT JOIN categorias c ON l.categoria_id = c.id 
                                  WHERE l.id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $libro = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // Obtener todas las categorías existentes
            $stmtCategorias = $db->query("SELECT id, nombre_categoria FROM categorias");
            $categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);
    
            // Renderizar la página de edición con el libro y las categorías
            echo $this->twig->render('editar_libro.twig', [
                'libro' => $libro,
                'categorias' => $categorias,
                'mensaje' => $mensaje ?? null,
                'tipoMensaje' => $tipoMensaje ?? null
            ]);
        } catch (PDOException $e) {
            echo "Error al obtener libro: " . $e->getMessage();
            error_log("Error en editarLibro(): " . $e->getMessage());
        }
    }
    
    // Eliminar libro
    public function eliminarLibro($params) {
        $id = (int)$params['id'];
        try {
            $db = (new Database())->connect();
            $stmt = $db->prepare("DELETE FROM libros WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            header("Location: /admin");
            exit;
        } catch (PDOException $e) {
            echo "Error al eliminar libro: " . $e->getMessage();
        }
    }
}