<?php
// Conexión a la base de datos
require_once __DIR__ . '/../config/Database.php';

use Config\Database; // Importar la clase Database desde el namespace Config

try {
    $pdo = Database::getConnection(); // Usar el método estático para obtener la conexión

    // Abrir el archivo CSV
    if (($handle = fopen(__DIR__ . "/../scraping/data/books.csv", "r")) !== false) {
        // Leer y omitir la primera línea de encabezados
        fgetcsv($handle);

        while (($data = fgetcsv($handle)) !== false) {
            // Asignar los valores del CSV a variables
            $titulo = $data[0];
            $autor_nombre = $data[1]; // Cambiado a autor_nombre
            $descripcion = $data[2];
            $formato = $data[3];

            // Verificar y extraer el número de páginas
            $num_paginas = preg_replace('/[^0-9]/', '', $data[4]); // Eliminar caracteres no numéricos
            $num_paginas = is_numeric($num_paginas) ? (int)$num_paginas : null; // Convertir a entero o asignar null

            // Verificar y procesar la fecha de publicación
            // Posibles entradas: 
            // "Published October 22, 2024", "Expected publication February 4, 2025" o "First published December 20, 1817"
            $fechaStr = trim($data[5]);
            // Eliminar prefijos conocidos
            $fechaStr = str_replace(['Published', 'Expected publication', 'First published'], '', $fechaStr);
            $fechaStr = trim($fechaStr);
            $timestamp = strtotime($fechaStr);
            $fecha_publicacion = $timestamp ? date('Y-m-d', $timestamp) : null;

            // Verificar y extraer el número de calificaciones
            $num_calificaciones = preg_replace('/[^0-9]/', '', $data[7]); // Eliminar caracteres no numéricos
            $num_calificaciones = is_numeric($num_calificaciones) ? (int)$num_calificaciones : null; // Convertir a entero o asignar null

            // Validar num_resenas: extraer solo el valor numérico si es necesario
            $num_resenas = preg_replace('/\D/', '', $data[8]); // Eliminar caracteres no numéricos
            $num_resenas = is_numeric($num_resenas) ? (int)$num_resenas : 0; // Asignar 0 si no es un número

            // Asignar valores de imagen_url y libro_url
            $imagen_url = $data[9];
            $libro_url = $data[10];
            $categoria = trim($data[11]); // Limpiar espacios

            // Buscar el ID del autor
            $queryAutor = "SELECT id FROM autores WHERE nombre_autor = :autor_nombre LIMIT 1";
            $stmtAutor = $pdo->prepare($queryAutor);
            $stmtAutor->bindValue(':autor_nombre', $autor_nombre, PDO::PARAM_STR);
            $stmtAutor->execute();

            $autor_id = $stmtAutor->fetchColumn();

            // Si el autor no existe, insertarlo
            if (!$autor_id) {
                $sqlAutor = "INSERT INTO autores (nombre_autor) VALUES (:autor_nombre)";
                $stmtInsertAutor = $pdo->prepare($sqlAutor);
                $stmtInsertAutor->execute([':autor_nombre' => $autor_nombre]);
                $autor_id = $pdo->lastInsertId();
                echo "Autor '$autor_nombre' creado con éxito.\n";
            }

            // Buscar el ID de la categoría
            $queryCategoria = "SELECT id FROM categorias WHERE nombre_categoria = :categoria LIMIT 1";
            $stmtCategoria = $pdo->prepare($queryCategoria);
            $stmtCategoria->bindValue(':categoria', $categoria, PDO::PARAM_STR);
            $stmtCategoria->execute();

            $categoria_id = $stmtCategoria->fetchColumn();

            // Si la categoría no existe, insertarla
            if (!$categoria_id) {
                $sqlCategoria = "INSERT INTO categorias (nombre_categoria) VALUES (:categoria)";
                $stmtInsertCategoria = $pdo->prepare($sqlCategoria);
                $stmtInsertCategoria->execute([':categoria' => $categoria]);
                $categoria_id = $pdo->lastInsertId();
                echo "Categoría '$categoria' creada con éxito.\n";
            }

            // Verificar si el libro ya existe
            $queryLibro = "SELECT id FROM libros WHERE titulo = :titulo AND autor_id = :autor_id LIMIT 1";
            $stmtLibro = $pdo->prepare($queryLibro);
            $stmtLibro->bindValue(':titulo', $titulo, PDO::PARAM_STR);
            $stmtLibro->bindValue(':autor_id', $autor_id, PDO::PARAM_INT);
            $stmtLibro->execute();

            $libro_id = $stmtLibro->fetchColumn();

            if ($libro_id) {
                echo "El libro '$titulo' ya existe en la base de datos.\n";
            } else {
                // Preparar y ejecutar la inserción de los datos del libro
                $sql = "INSERT INTO libros (titulo, descripcion, formato, num_paginas, fecha_publicacion, num_calificaciones, num_resenas, imagen_url, libro_url, categoria_id, autor_id) 
                        VALUES (:titulo, :descripcion, :formato, :num_paginas, :fecha_publicacion, :num_calificaciones, :num_resenas, :imagen_url, :libro_url, :categoria_id, :autor_id)";

                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':titulo' => $titulo,
                    ':descripcion' => $descripcion,
                    ':formato' => $formato,
                    ':num_paginas' => $num_paginas,
                    ':fecha_publicacion' => $fecha_publicacion,
                    ':num_calificaciones' => $num_calificaciones,
                    ':num_resenas' => $num_resenas,
                    ':imagen_url' => $imagen_url,
                    ':libro_url' => $libro_url,
                    ':categoria_id' => $categoria_id,
                    ':autor_id' => $autor_id
                ]);

                echo "Libro '$titulo' insertado correctamente.\n";
            }
        }

        fclose($handle);
    } else {
        echo "Error: No se pudo abrir el archivo CSV.\n";
    }

} catch (PDOException $e) {
    echo "Error en la base de datos: " . $e->getMessage();
}
