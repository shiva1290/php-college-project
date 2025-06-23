<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Get database connection
$db = getDB();
if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to post']);
    exit;
}

$userId = getCurrentUserId();

// Check if user can post today
if (!canPostToday($userId)) {
    http_response_code(403);
    echo json_encode(['error' => 'You can only post one truth per day']);
    exit;
}

// Check if user has read enough posts
if (!hasReadEnough($userId)) {
    http_response_code(403);
    echo json_encode(['error' => 'You need to read at least 3 posts before posting again']);
    exit;
}

// Get and validate content
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
if (empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Content cannot be empty']);
    exit;
}

try {
    // Generate unique post ID
    $postId = bin2hex(random_bytes(16));

    // Start transaction
    $db->beginTransaction();

    // Insert the post
    $stmt = $db->prepare("
        INSERT INTO posts (post_id, user_id, content) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$postId, $userId, $content]);

    // Update user's last post date
    $stmt = $db->prepare("
        UPDATE users 
        SET last_post_date = CURRENT_DATE 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);

    // Commit transaction
    $db->commit();

    // Return success
    echo json_encode([
        'success' => true,
        'post_id' => $postId,
        'message' => 'Your truth has been shared successfully'
    ]);

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Post Creation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create post']);
    exit;
} 