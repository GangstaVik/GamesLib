<?php
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// XML file paths
define('USERS_XML', __DIR__.'/../lib/users.xml');
define('GAMES_XML', __DIR__.'/../libreria.xml');

// Initialize users XML if not exists
if (!file_exists(USERS_XML)) {
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Users></Users>');
    $xml->asXML(USERS_XML);
}

// Password hashing options
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_OPTIONS', ['cost' => 12]);

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}