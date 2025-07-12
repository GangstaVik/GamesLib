<?php
require_once 'config.php';

function registerUser($username, $password) {
    $xml = simplexml_load_file(USERS_XML);
    
    // Check if user exists
    foreach ($xml->user as $user) {
        if ((string)$user->username === $username) {
            return false; // User exists
        }
    }
    
    // Add new user
    $user = $xml->addChild('user');
    $user->addChild('username', htmlspecialchars($username));
    $user->addChild('password', password_hash($password, PASSWORD_ALGO, PASSWORD_OPTIONS));
    
    return $xml->asXML(USERS_XML);
}

function loginUser($username, $password) {
    $xml = simplexml_load_file(USERS_XML);
    
    foreach ($xml->user as $user) {
        if ((string)$user->username === $username) {
            if (password_verify($password, (string)$user->password)) {
                $_SESSION['user'] = [
                    'username' => $username,
                    'logged_in' => true
                ];
                return true;
            }
            break;
        }
    }
    
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user']['logged_in']) && $_SESSION['user']['logged_in'] === true;
}

function logoutUser() {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}