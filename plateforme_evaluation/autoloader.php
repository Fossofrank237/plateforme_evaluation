<?php

// Créez un fichier autoload.php
spl_autoload_register(function ($class) {
    $path = str_replace('\\', '/', $class);
    require_once __DIR__ . '/../classes/' . $path . '.php';
});

// // Puis dans login.php, utilisez seulement :
// require 'autoload.php';

?>