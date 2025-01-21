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
            $autor = $data[1];
            $descripcion = $data[2];
            $formato = $data[3];

            // Verificar y extraer el número de páginas
            $num_paginas = preg_replace('/[^0-9]/', '', $data[4]); // Eliminar caracteres no numéricos
            $num_paginas = is_numeric($num_paginas) ? (int)$num_paginas : null; // Convertir a entero o asignar null

            // Verificar si la fecha de publicación es válida
            $fecha_publicacion = date('Y-m-d', strtotime($data[5]));

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

            // Buscar el ID de la categoría
            $queryCategoria = "SELECT id FROM categorias WHERE nombre_categoria = :categoria LIMIT 1";
            $stmtCategoria = $pdo->prepare($queryCategoria);
            $stmtCategoria->bindValue(':categoria', $categoria, PDO::PARAM_STR);
            $stmtCategoria->execute();

            $categoria_id = $stmtCategoria->fetchColumn();

            // Si la categoría no existe, insertarla
            if (!$categoria_id) {
                // Insertar la categoría si no existe
                $sqlCategoria = "INSERT INTO categorias (nombre_categoria) VALUES (:categoria)";
                $stmtInsertCategoria = $pdo->prepare($sqlCategoria);
                $stmtInsertCategoria->execute([':categoria' => $categoria]);
                $categoria_id = $pdo->lastInsertId(); // Obtener el último id insertado
                echo "Categoría '$categoria' creada con éxito.\n";
            }

            // Preparar y ejecutar la inserción de los datos del libro
            $sql = "INSERT INTO datos_scrapeados (titulo, autor, descripcion, formato, num_paginas, fecha_publicacion, num_calificaciones, num_resenas, imagen_url, libro_url, categoria_id) 
                    VALUES (:titulo, :autor, :descripcion, :formato, :num_paginas, :fecha_publicacion, :num_calificaciones, :num_resenas, :imagen_url, :libro_url, :categoria_id)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titulo' => $titulo,
                ':autor' => $autor,
                ':descripcion' => $descripcion,
                ':formato' => $formato,
                ':num_paginas' => $num_paginas,
                ':fecha_publicacion' => $fecha_publicacion,
                ':num_calificaciones' => $num_calificaciones,
                ':num_resenas' => $num_resenas,
                ':imagen_url' => $imagen_url,
                ':libro_url' => $libro_url,
                ':categoria_id' => $categoria_id
            ]);

            echo "Libro '$titulo' insertado correctamente.\n";
        }

        fclose($handle);
    } else {
        echo "Error: No se pudo abrir el archivo CSV.\n";
    }

} catch (PDOException $e) {
    echo "Error en la base de datos: " . $e->getMessage();
}
