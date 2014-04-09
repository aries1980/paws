<?php

// Ensure the UI sends and receives Unicode strings.
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

if (!defined('PAWS_ROOT_DIR')) {
    define('PAWS_ROOT_DIR', dirname(__DIR__));
    defined('PAWS_WEB_DIR') or define('PAWS_WEB_DIR', PAWS_ROOT_DIR);
    defined('PAWS_CACHE_DIR') or define('PAWS_CACHE_DIR', PAWS_ROOT_DIR . '/app/cache');

    // Set the config folder location. If we haven't set the constant in index.php, use one of the
    // default values.
    if (!defined('PAWS_CONFIG_DIR')) {
        if (is_dir(__DIR__ . '/config')) {
            // Default value, /app/config/..
            define('PAWS_CONFIG_DIR', __DIR__ . '/config');
        } else {
            // otherwise use /config, outside of the webroot folder.
            define('PAWS_CONFIG_DIR', dirname(dirname(__DIR__)) . '/config');
        }
    }
}

// Loading the class auto loader.
require_once PAWS_ROOT_DIR . '/vendor/autoload.php';

// Create the 'PAWS application'.
$app = new Paws\Application();

// Initialize the 'PAWS application': Set up all routes, providers, database, rendering engine, etc..
$app->initialize();
