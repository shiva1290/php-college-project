<?php
require_once 'config.php';
require_once 'auth.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Validate post_id parameter
if (!isset($_POST['post_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing post_id parameter']);
    exit;
}

$postId = $_POST['post_id'];
$userId = getCurrentUserId();

try {
    $db = getDB();
    
    // Check if already read
    $checkStmt = $db->prepare("SELECT 1 FROM reading_history WHERE user_id = ? AND post_id = ?");
    $checkStmt->execute([$userId, $postId]);
    
    if (!$checkStmt->fetch()) {
        // Insert new reading record
        $insertStmt = $db->prepare("INSERT INTO reading_history (user_id, post_id, read_at) VALUES (?, ?, NOW())");
        $insertStmt->execute([$userId, $postId]);
    }
    
    http_response_code(200);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log("Database error in mark_read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
} 