<?php

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'Shiva'); 
define('DB_NAME', 'greyshot');


error_reporting(E_ALL);
ini_set('display_errors', 1);


session_start();


function getDB() {
    try {
        $db = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
        );
        return $db;
    } catch(PDOException $e) {
        error_log("Connection Error: " . $e->getMessage());
        return null;
    }
}

// Utility functions
function generateUniqueId() {
    return bin2hex(random_bytes(16));
}

function generateAccessToken() {
    return bin2hex(random_bytes(32));
}

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Rate limiting function
function checkRateLimit($ip, $db) {
    $stmt = $db->prepare("SELECT * FROM rate_limits WHERE ip_address = ?");
    $stmt->execute([$ip]);
    $limit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$limit) {
        // First post from this IP
        $stmt = $db->prepare("INSERT INTO rate_limits (ip_address) VALUES (?)");
        $stmt->execute([$ip]);
        return true;
    }
    
    $timeDiff = time() - strtotime($limit['last_post_time']);
    if ($timeDiff < 60 && $limit['post_count'] >= 3) {
        // More than 3 posts in a minute
        return false;
    }
    
    if ($timeDiff >= 60) {
        // Reset counter after a minute
        $stmt = $db->prepare("UPDATE rate_limits SET post_count = 1, last_post_time = CURRENT_TIMESTAMP WHERE ip_address = ?");
    } else {
        // Increment counter
        $stmt = $db->prepare("UPDATE rate_limits SET post_count = post_count + 1 WHERE ip_address = ?");
    }
    $stmt->execute([$ip]);
    return true;
}

function timeAgo($timestamp) {
    $datetime = new DateTime($timestamp);
    $now = new DateTime();
    $interval = $now->diff($datetime);
    
    if ($interval->y > 0) {
        return $interval->y . 'y ago';
    }
    if ($interval->m > 0) {
        return $interval->m . 'mo ago';
    }
    if ($interval->d > 0) {
        return $interval->d . 'd ago';
    }
    if ($interval->h > 0) {
        return $interval->h . 'h ago';
    }
    if ($interval->i > 0) {
        return $interval->i . 'm ago';
    }
    return 'just now';
} 