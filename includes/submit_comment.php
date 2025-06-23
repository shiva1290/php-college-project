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

// Check if user is logged in
error_log("Session status: " . session_status());
error_log("Session ID: " . session_id());
error_log("User ID in session: " . ($_SESSION['user_id'] ?? 'not set'));

if (!isLoggedIn()) {
    error_log("User not logged in");
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to comment']);
    exit;
}

$userId = getCurrentUserId();
error_log("Current user ID: " . $userId);

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get and validate input
$postId = isset($_POST['post_id']) ? $_POST['post_id'] : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if (empty($postId) || empty($content)) {
    http_response_code(400);
    echo json_encode(['error' => 'Post ID and content are required']);
    exit;
}

try {
    // Verify post exists
    $stmt = $db->prepare("SELECT 1 FROM posts WHERE post_id = ? AND is_deleted = FALSE");
    $stmt->execute([$postId]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit;
    }

    // Insert the comment with user_id
    $stmt = $db->prepare("
        INSERT INTO comments (post_id, user_id, content, is_approved) 
        VALUES (?, ?, ?, FALSE)
    ");
    
    $stmt->execute([$postId, $userId, $content]);

    // Get the comment details for pending display
    $commentId = $db->lastInsertId();
    $stmt = $db->prepare("
        SELECT 
            c.comment_id,
            c.content,
            c.created_at,
            u.username,
            u.icon_seed
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.user_id
        WHERE c.comment_id = ?
    ");
    
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return success with pending message
    echo json_encode([
        'success' => true,
        'message' => 'Your comment has been submitted and is awaiting approval. This helps maintain the quality of our community discussions.',
        'comment' => $comment,
        'isPending' => true
    ]);

} catch (PDOException $e) {
    error_log("Comment Creation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to submit comment']);
    exit;
} 