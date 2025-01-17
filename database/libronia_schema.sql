-- Crear la base de datos (si no existe)
CREATE DATABASE IF NOT EXISTS libronia
CHARACTER SET utf8mb4
COLLATE utf8mb4_general_ci;

-- Usar la base de datos
USE libronia;

-- Crear la tabla usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,                -- Identificador único del usuario
    nombre VARCHAR(255) NOT NULL,                      -- Nombre del usuario
    correo VARCHAR(255) NOT NULL UNIQUE,               -- Correo electrónico único
    contrasena VARCHAR(255) NOT NULL,                  -- Contraseña encriptada
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Fecha de creación automática
);

-- Crear la tabla categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,               -- Identificador único de la categoría
    nombre_categoria VARCHAR(255) UNIQUE NOT NULL,   -- Nombre único de la categoría
    descripcion TEXT                                 -- Descripción de la categoría (opcional)
);

-- Crear la tabla datos_scrapeados
CREATE TABLE IF NOT EXISTS datos_scrapeados (
    id INT AUTO_INCREMENT PRIMARY KEY,               -- Identificador único del dato scrapeado
    titulo VARCHAR(255) NOT NULL,                    -- Título del libro u objeto scrapeado
    autor VARCHAR(255) NOT NULL,                     -- Autor del libro
    descripcion TEXT,                                -- Descripción o sinopsis del libro
    formato VARCHAR(50),                             -- Formato del libro (PDF, físico, etc.)
    num_paginas INT,                                 -- Número de páginas
    fecha_publicacion DATE,                          -- Fecha de publicación del libro
    num_calificaciones INT,                          -- Número de calificaciones
    num_resenas INT,                                 -- Número de reseñas
    imagen_url VARCHAR(255),                         -- URL de la imagen del libro
    libro_url VARCHAR(255),                          -- URL del libro o referencia
    categoria_id INT,                                -- Relación con la tabla categorías
    usuario_id INT,                                  -- Relación con el usuario asociado (si aplica)
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
);
