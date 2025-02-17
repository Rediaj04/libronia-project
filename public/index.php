<?php

require_once __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Obtener idioma de la URL o sesión (por defecto español)
$lang = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'es_ES');
$_SESSION['lang'] = $lang;
error_log("Idioma seleccionado: " . $lang);

// Configurar el entorno para gettext

putenv("LANG=$lang.utf8");
putenv("LC_ALL=$lang.utf8");
setlocale(LC_ALL, "$lang.utf8");
error_log("Locale después de configurar: " . setlocale(LC_ALL, 0));

// Configurar gettext
$domain = 'messages';
bindtextdomain($domain, realpath(__DIR__ . '/../locales'));
bind_textdomain_codeset($domain, 'UTF-8');
textdomain($domain);

// Verificar si las traducciones se están cargando correctamente
error_log("Current textdomain: " . textdomain(null));
error_log("Translation test: " . _("Libronia - Tu biblioteca digital"));

// Verificar la ruta de los archivos de traducción
$mofile = realpath(__DIR__ . "/../locales/$lang/LC_MESSAGES/messages.mo");
error_log("MO file path: $mofile");
if (file_exists($mofile)) {
    error_log("MO file exists");
} else {
    error_log("MO file does not exist");
}

// Incluir el archivo Router.php
require_once __DIR__ . '/../src/Routing/Router.php';