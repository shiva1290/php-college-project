<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'You must be logged in to delete posts']);
    exit;
}

// Get post ID from request
$postId = $_POST['post_id'] ?? '';
if (empty($postId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing post ID']);
    exit;
}

$userId = getCurrentUserId();

// Try to delete the post
$result = deletePost($userId, $postId);

if (isset($result['error'])) {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
} else {
    echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
} 