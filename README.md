# Libronia - Tu Biblioteca Digital

<div align="center">

<img src="public/assets/img/logo.png" alt="Libronia Logo" width="200" height="auto">

Sistema de gestión bibliotecaria moderno y eficiente

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://www.php.net/)
[![Twig Version](https://img.shields.io/badge/Twig-3.0-green.svg)](https://twig.symfony.com/)

</div>

## 📚 Descripción

Libronia es un sistema de gestión bibliotecaria que permite administrar libros, categorías y usuarios. Desarrollado con PHP moderno y siguiendo las mejores prácticas de desarrollo.

## 🗂️ Estructura del Proyecto

```
📦 libronia
├── 📂 config/               # Configuraciones de la aplicación
│   ├── Database.php        # Configuración de base de datos
│   ├── JWTAuth.php         # Configuración de autenticación
│   └── TwigSetup.php       # Configuración de Twig
├── 📂 database/            # Archivos de base de datos
│   └── libronia_schema.sql # Esquema de la base de datos
├── 📂 locales/             # Archivos de internacionalización
│   ├── en_US/
│   ├── es_ES/
│   └── pt_BR/
├── 📂 scraping/            # Scripts de web scraping
│   ├── config.py
│   ├── main.py
│   ├── utils.py
│   └── requirements.txt
├── 📂 script/              # Scripts de utilidad
│   ├── insert_admin.php
│   ├── insertar_categorias.php
│   └── insertar_datos_csv.php
├── 📂 src/                 # Código fuente principal
│   ├── Controller/
│   ├── Middleware/
│   ├── Models/
│   └── Routing/
├── 📂 templates/           # Plantillas Twig
└── 📂 vendor/              # Dependencias de Composer
```

## ⚡ Características

- **Autenticación Segura**: Sistema JWT para gestión de sesiones
- **Internacionalización**: Soporte para múltiples idiomas (ES, EN, PT)
- **Web Scraping**: Importación automática de datos de libros
- **Panel Admin**: Gestión completa de libros y usuarios
- **Responsive**: Interfaz adaptable a todos los dispositivos

## 🛠️ Requisitos

- PHP 8.0 o superior
- MySQL/MariaDB
- Python 3.8+ (para web scraping)
- Composer
- Extensiones PHP:
  - PDO
  - JSON
  - mbstring

## ⚙️ Instalación

1. **Clonar el repositorio**
```bash
git clone https://github.com/Rediaj04/libronia.git
cd libronia
```

2. **Instalar dependencias PHP**
```bash
composer install
```

3. **Configurar base de datos**
```bash
mysql -u root -p < database/libronia_schema.sql
```

4. **Configurar Python (para web scraping)**
```bash
cd scraping
python -m venv venv
source venv/bin/activate  # En Windows: venv\Scripts\activate
pip install -r requirements.txt
```

5. **Inicializar datos**
```bash
php script/insert_admin.php
php script/insertar_categorias.php
```

6. **Configurar Virtual Host (Linux/Apache)**
```bash
# Crear archivo de configuración del virtual host
sudo nano /etc/apache2/sites-available/libronia.conf

# Añadir la siguiente configuración
<VirtualHost *:80>
    ServerName libronia.local
    DocumentRoot /var/www/html/libronia/public
    
    <Directory /var/www/html/libronia/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/libronia_error.log
    CustomLog ${APACHE_LOG_DIR}/libronia_access.log combined
</VirtualHost>

# Habilitar el sitio
sudo a2ensite libronia.conf

# Agregar dominio al archivo hosts
sudo nano /etc/hosts
# Añadir: 127.0.0.1 libronia.local

# Reiniciar Apache
sudo systemctl restart apache2
```

7. **Configurar Permisos**
```bash
# Establecer permisos correctos para directorios de carga
sudo chown -R www-data:www-data /var/www/html/libronia/public/uploads
sudo chmod -R 755 /var/www/html/libronia/public/uploads

# Permisos para logs y caché
sudo chown -R www-data:www-data /var/www/html/libronia/storage/logs
sudo chmod -R 755 /var/www/html/libronia/storage/logs
sudo chown -R www-data:www-data /var/www/html/libronia/storage/cache
sudo chmod -R 755 /var/www/html/libronia/storage/cache

# Asegurar que el archivo .htaccess es legible
sudo chmod 644 /var/www/html/libronia/public/.htaccess
```

8. **Configurar PHP**
```bash
# Editar php.ini
sudo nano /etc/php/8.0/apache2/php.ini

# Ajustar los siguientes valores
upload_max_filesize = 10M
post_max_size = 10M
memory_limit = 256M
max_execution_time = 300
```

## 🔒 Seguridad

1. **Proteger Directorios Sensibles**
```apache
# Añadir en .htaccess
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Proteger directorios de configuración
<DirectoryMatch "^/.*/config/">
    Deny from all
</DirectoryMatch>
```

2. **Configurar CORS**
```apache
# Añadir en .htaccess
Header set Access-Control-Allow-Origin "http://libronia.local"
Header set Access-Control-Allow-Methods "GET,POST,PUT,DELETE"
Header set Access-Control-Allow-Headers "Content-Type, Authorization"
```

## 🔍 Solución de Problemas Comunes

1. **Error de Permisos en Uploads**
```bash
# Verificar permisos actuales
ls -la /var/www/html/libronia/public/uploads

# Reestablecer permisos si es necesario
sudo chown -R www-data:www-data /var/www/html/libronia/public/uploads
sudo chmod -R 755 /var/www/html/libronia/public/uploads
```

2. **Error 500 en Apache**
```bash
# Verificar logs de error
sudo tail -f /var/log/apache2/error.log

# Verificar logs de la aplicación
tail -f /var/www/html/libronia/storage/logs/error.log
```

3. **Problemas con mod_rewrite**
```bash
# Habilitar mod_rewrite
sudo a2enmod rewrite

# Verificar configuración
apache2ctl -M | grep rewrite

# Reiniciar Apache
sudo systemctl restart apache2
```

## 🚀 Uso

1. **Iniciar servidor de desarrollo**
```bash
php -S localhost:8000
```

2. **Acceder a la aplicación**
- Abrir navegador en `http://localhost:8000` o `http://libronia.local/`
- Credenciales por defecto:
  - Usuario: admin@libronia.com
  - Contraseña: admin123

## 📝 Licencia

Este proyecto está bajo la [Licencia MIT](LICENSE).

## 👥 Contribuir

1. Fork el proyecto
2. Crea tu rama de feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add: nueva característica'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

---
<div align="center">
  <a href="https://github.com/Rediaj04">
    <img src="https://github.com/Rediaj04.png" width="100" height="100" style="border-radius: 50%;">
    <br>
    <sub>@Rediaj04</sub>
  </a>
  <br>
  Desarrollado con ❤️
</div>
