<?php
// Path bootstrap compatibility layer

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', __DIR__);
}

if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', BASE_PATH . '/assets');
}

if (!defined('FRONTEND_PATH')) {
    define('FRONTEND_PATH', BASE_PATH . '/frontend');
}

if (!defined('INCLUDES_PATH')) {
    define('INCLUDES_PATH', BASE_PATH . '/includes');
}

if (!defined('API_PATH')) {
    define('API_PATH', BASE_PATH . '/api');
}

