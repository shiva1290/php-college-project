<?php
require_once 'config.php';

function generateUsername() {
    $adjectives = [
        // Colors
        'Azure', 'Crimson', 'Golden', 'Silver', 'Emerald', 'Violet', 'Sapphire', 'Amber', 'Obsidian', 'Pearl',
        // Personality
        'Mystic', 'Brave', 'Silent', 'Wise', 'Noble', 'Swift', 'Gentle', 'Fierce', 'Clever', 'Bold',
        // Nature
        'Wild', 'Serene', 'Cosmic', 'Solar', 'Lunar', 'Storm', 'Desert', 'Forest', 'Ocean', 'Mountain',
        // Elemental
        'Frost', 'Flame', 'Thunder', 'Shadow', 'Light', 'Crystal', 'Steel', 'Terra', 'Aether', 'Void',
        // Mythical
        'Dragon', 'Phoenix', 'Griffin', 'Titan', 'Oracle', 'Fable', 'Legend', 'Mythic', 'Ancient', 'Eternal'
    ];

    $nouns = [
        // Nature
        'Wave', 'Star', 'Moon', 'Sun', 'Wind', 'River', 'Cloud', 'Storm', 'Rain', 'Dawn',
        // Animals
        'Wolf', 'Eagle', 'Lion', 'Raven', 'Hawk', 'Tiger', 'Bear', 'Fox', 'Owl', 'Falcon',
        // Elements
        'Fire', 'Ice', 'Earth', 'Wind', 'Storm', 'Frost', 'Flame', 'Stone', 'Steel', 'Crystal',
        // Celestial
        'Nova', 'Comet', 'Galaxy', 'Nebula', 'Aurora', 'Cosmos', 'Astro', 'Meteor', 'Orbit', 'Quasar',
        // Objects
        'Blade', 'Shield', 'Crown', 'Scepter', 'Tome', 'Rune', 'Sigil', 'Prism', 'Scroll', 'Gem'
    ];

    $connectors = ['Of', 'The', 'In', 'From', 'Beyond'];

    // 20% chance to add a connector and second part
    if (rand(1, 100) <= 20) {
        $connector = $connectors[array_rand($connectors)];
        $secondAdjOrNoun = rand(0, 1) ? $adjectives[array_rand($adjectives)] : $nouns[array_rand($nouns)];
        $username = $adjectives[array_rand($adjectives)] . $connector . $secondAdjOrNoun;
    } else {
        $username = $adjectives[array_rand($adjectives)] . $nouns[array_rand($nouns)];
    }

    // Add a random number between 1 and 999, formatted to always be 3 digits
    $username .= sprintf('%03d', rand(1, 999));
    
    return $username;
}

function generateIconSeed() {
    return md5(uniqid(rand(), true));
}

function registerUser($username, $password) {
    $db = getDB();
    if (!$db) return false;

    try {
        // Check if username exists
        $stmt = $db->prepare("SELECT 1 FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['error' => 'Username already exists'];
        }

        // Create user
        $userId = bin2hex(random_bytes(16));
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $iconSeed = generateIconSeed();

        $stmt = $db->prepare("
            INSERT INTO users (user_id, username, password_hash, icon_seed)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $username, $passwordHash, $iconSeed]);
        
        return ['success' => true, 'user_id' => $userId];
    } catch (PDOException $e) {
        error_log("Registration Error: " . $e->getMessage());
        return ['error' => 'Registration failed'];
    }
}

function loginUser($username, $password) {
    $db = getDB();
    if (!$db) return false;

    try {
        $stmt = $db->prepare("
            SELECT user_id, password_hash 
            FROM users 
            WHERE username = ?
        ");
        
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            return ['success' => true];
        }

        return ['error' => 'Invalid username or password'];
    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        return ['error' => 'Login failed'];
    }
}

function logoutUser() {
    session_unset();
    session_destroy();
    return true;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function canPostToday($userId) {
    $db = getDB();
    if (!$db) return false;

    try {
        // Check if user has any active (non-deleted) posts today
        $stmt = $db->prepare("
            SELECT COUNT(*) as post_count 
            FROM posts 
            WHERE user_id = ? 
            AND DATE(created_at) = CURRENT_DATE
            AND is_deleted = FALSE
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // If no posts today, they can post
        if ($result['post_count'] == 0) {
            return true;
        }

        // Check if they've reacted to 3 posts since their last post
        $stmt = $db->prepare("
            SELECT COUNT(*) as reaction_count
            FROM reactions r
            WHERE r.user_id = ?
            AND r.created_at > (
                SELECT MAX(created_at)
                FROM posts
                WHERE user_id = ?
                AND DATE(created_at) = CURRENT_DATE
                AND is_deleted = FALSE
            )
        ");
        
        $stmt->execute([$userId, $userId]);
        $reactionResult = $stmt->fetch(PDO::FETCH_ASSOC);

        // They can post if they've reacted to 3 or more posts since their last post
        return $reactionResult['reaction_count'] >= 3;
    } catch (PDOException $e) {
        error_log("Post Check Error: " . $e->getMessage());
        return false;
    }
}

function hasReadEnough($userId) {
    $db = getDB();
    if (!$db) return false;

    try {
        // First check if user has any posts at all
        $stmt = $db->prepare("
            SELECT COUNT(*) as post_count
            FROM posts
            WHERE user_id = ? AND is_deleted = FALSE
        ");
        $stmt->execute([$userId]);
        $postResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If user has never posted, they don't need to read anything
        if ($postResult['post_count'] == 0) {
            return true;
        }

        // Check if user has read at least 3 posts since their last post
        $stmt = $db->prepare("
            SELECT COUNT(*) as read_count
            FROM reading_history
            WHERE user_id = ? AND read_at > (
                SELECT COALESCE(last_post_date, '1970-01-01')
                FROM users
                WHERE user_id = ?
            )
        ");
        
        $stmt->execute([$userId, $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['read_count'] >= 3;
    } catch (PDOException $e) {
        error_log("Read Check Error: " . $e->getMessage());
        return false;
    }
}

function markPostAsRead($userId, $postId) {
    $db = getDB();
    if (!$db) return false;

    try {
        $stmt = $db->prepare("
            INSERT IGNORE INTO reading_history (user_id, post_id)
            VALUES (?, ?)
        ");
        
        return $stmt->execute([$userId, $postId]);
    } catch (PDOException $e) {
        error_log("Mark Read Error: " . $e->getMessage());
        return false;
    }
}

function getUserProfile($userId) {
    $db = getDB();
    if (!$db) {
        error_log("getUserProfile: Database connection failed");
        return null;
    }

    try {
        error_log("getUserProfile: Looking up user with ID: " . $userId);
        $stmt = $db->prepare("
            SELECT username, icon_seed, created_at
            FROM users
            WHERE user_id = ?
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            error_log("getUserProfile: No user found with ID: " . $userId);
            return null;
        }
        
        return $result;
    } catch (PDOException $e) {
        error_log("getUserProfile Error: " . $e->getMessage());
        return null;
    }
}

function deletePost($userId, $postId) {
    $db = getDB();
    if (!$db) return false;

    try {
        // Verify the post belongs to the user and is from today
        $stmt = $db->prepare("
            SELECT 1 
            FROM posts 
            WHERE post_id = ? 
            AND user_id = ? 
            AND DATE(created_at) = CURRENT_DATE
            AND is_deleted = FALSE
        ");
        
        $stmt->execute([$postId, $userId]);
        if (!$stmt->fetch()) {
            return ['error' => 'Post not found or cannot be deleted'];
        }

        // Soft delete the post
        $stmt = $db->prepare("
            UPDATE posts 
            SET is_deleted = TRUE 
            WHERE post_id = ? 
            AND user_id = ?
        ");
        
        $stmt->execute([$postId, $userId]);
        return ['success' => true];
    } catch (PDOException $e) {
        error_log("Delete Post Error: " . $e->getMessage());
        return ['error' => 'Failed to delete post'];
    }
} 