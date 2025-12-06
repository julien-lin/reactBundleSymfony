<?php

/**
 * PHPUnit Bootstrap
 * Initialise l'environnement des tests
 */

// Déterminer le chemin correct vers autoload
$paths = [
    __DIR__ . '/../vendor/autoload.php',  // Si le bundle est en standalone
    __DIR__ . '/../../../../vendor/autoload.php',  // Si le bundle est dans vendor/
];

$autoloadFound = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $autoloadFound = true;
        break;
    }
}

if (!$autoloadFound) {
    throw new \RuntimeException('Autoloader not found. Please run composer install.');
}

// Constantes de test
define('REACT_BUNDLE_PATH', dirname(__DIR__));
define('REACT_BUNDLE_TEST_PATH', __DIR__);

// Configuration d'erreur pour les tests
error_reporting(-1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
