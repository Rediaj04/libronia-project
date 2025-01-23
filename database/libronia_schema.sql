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
    nombre_categoria VARCHAR(255) UNIQUE NOT NULL     -- Nombre único de la categoría
);

-- Crear la tabla autores
CREATE TABLE IF NOT EXISTS autores (
    id INT AUTO_INCREMENT PRIMARY KEY,                -- Identificador único del autor
    nombre_autor VARCHAR(255) UNIQUE NOT NULL         -- Nombre único del autor
);

-- Crear la tabla libros
CREATE TABLE IF NOT EXISTS libros (
    id INT AUTO_INCREMENT PRIMARY KEY,               -- Identificador único del libro
    titulo VARCHAR(255) NOT NULL,                    -- Título del libro
    descripcion TEXT,                                -- Descripción o sinopsis del libro
    formato VARCHAR(50),                             -- Formato del libro (PDF, físico, etc.)
    num_paginas INT,                                 -- Número de páginas
    fecha_publicacion DATE,                          -- Fecha de publicación del libro
    num_calificaciones INT,                          -- Número de calificaciones
    num_resenas INT,                                 -- Número de reseñas
    imagen_url VARCHAR(255),                         -- URL de la imagen del libro
    libro_url VARCHAR(255),                          -- URL del libro o referencia
    categoria_id INT,                                -- Relación con la tabla categorías
    autor_id INT,                                    -- Relación con la tabla autores
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (autor_id) REFERENCES autores(id) ON DELETE SET NULL ON UPDATE CASCADE,
    UNIQUE (titulo, autor_id)                        -- Restricción de unicidad para título y autor_id
);