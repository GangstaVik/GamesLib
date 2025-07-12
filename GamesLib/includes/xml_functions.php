<?php
require_once 'config.php';

/**
 * Prettify XML string with proper indentation
 */
function prettifyXML($xmlString) {
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xmlString);
    
    // Add XML comment with generation info
    $comment = $dom->createComment(sprintf(
        "\n    Generated on %s by %s\n    ",
        date('Y-m-d H:i:s'),
        $_SESSION['user']['username'] ?? 'system'
    ));
    $dom->insertBefore($comment, $dom->firstChild);
    
    return $dom->saveXML();
}

/**
 * Get user-specific games XML file path
 */
function getUserGamesXML() {
    if (!isset($_SESSION['user']['username'])) {
        return GAMES_XML;
    }
    
    $userDir = __DIR__.'/../lib/users/';
    if (!file_exists($userDir)) {
        mkdir($userDir, 0755, true);
    }
    
    return $userDir.'games_'.md5($_SESSION['user']['username']).'.xml';
}

/**
 * Initialize user-specific games XML if not exists
 */
function initUserGamesXML() {
    $userFile = getUserGamesXML();
    
    if (!file_exists($userFile)) {
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Lib></Lib>');
        
        // Add metadata
        $xml->addAttribute('owner', $_SESSION['user']['username']);
        $xml->addAttribute('created', date('c'));
        
        // Save prettified XML
        file_put_contents($userFile, prettifyXML($xml->asXML()));
    }
    
    return $userFile;
}