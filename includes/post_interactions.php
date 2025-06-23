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

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to perform this action']);
    exit;
}

if (!isset($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$action = $_POST['action'];
$userId = $_SESSION['user_id'];

// Check for post_id requirement based on action
if (!in_array($action, ['approve_comment', 'reject_comment']) && !isset($_POST['post_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Post ID not provided']);
    exit;
}

// Handle upvote
function handleUpvote($db, $userId, $postId) {
    try {
        // Check if already upvoted
        $stmt = $db->prepare("SELECT 1 FROM upvotes WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $userId]);
        
        if ($stmt->fetch()) {
            // Remove upvote
            $stmt = $db->prepare("DELETE FROM upvotes WHERE post_id = ? AND user_id = ?");
            $stmt->execute([$postId, $userId]);
            return ['success' => true, 'action' => 'removed'];
        } else {
            // Add upvote
            $stmt = $db->prepare("INSERT INTO upvotes (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$postId, $userId]);
            return ['success' => true, 'action' => 'added'];
        }
    } catch (PDOException $e) {
        error_log("Upvote Error: " . $e->getMessage());
        return ['error' => 'Failed to process upvote'];
    }
}

// Handle reaction
function handleReaction($db, $userId, $postId, $reactionType) {
    if (!in_array($reactionType, ['relate', 'needed', 'thanks'])) {
        return ['error' => 'Invalid reaction type'];
    }

    try {
        // Check if reaction exists
        $stmt = $db->prepare("
            SELECT 1 FROM reactions 
            WHERE post_id = ? AND user_id = ? AND reaction_type = ?
        ");
        $stmt->execute([$postId, $userId, $reactionType]);
        
        if ($stmt->fetch()) {
            // Remove reaction
            $stmt = $db->prepare("
                DELETE FROM reactions 
                WHERE post_id = ? AND user_id = ? AND reaction_type = ?
            ");
            $stmt->execute([$postId, $userId, $reactionType]);
            return ['success' => true, 'action' => 'removed'];
        } else {
            // Add reaction
            $stmt = $db->prepare("
                INSERT INTO reactions (post_id, user_id, reaction_type) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$postId, $userId, $reactionType]);
            return ['success' => true, 'action' => 'added'];
        }
    } catch (PDOException $e) {
        error_log("Reaction Error: " . $e->getMessage());
        return ['error' => 'Failed to process reaction'];
    }
}

try {
    switch ($action) {
        case 'upvote':
            $postId = $_POST['post_id'];
            $result = handleUpvote($db, $userId, $postId);
            if (isset($result['error'])) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $result['error']]);
            } else {
                echo json_encode($result);
            }
            break;

        case 'react':
            if (!isset($_POST['type'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Reaction type not provided']);
                exit;
            }
            $postId = $_POST['post_id'];
            $reactionType = $_POST['type'];
            $result = handleReaction($db, $userId, $postId, $reactionType);
            if (isset($result['error'])) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => $result['error']]);
            } else {
                echo json_encode($result);
            }
            break;

        case 'approve_comment':
            if (!isset($_POST['comment_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Comment ID not provided']);
                exit;
            }

            $commentId = filter_var($_POST['comment_id'], FILTER_SANITIZE_NUMBER_INT);
            
            // Get the comment and associated post info
            $stmt = $db->prepare("
                SELECT c.is_approved, c.is_deleted, c.post_id, p.user_id as post_owner_id 
                FROM comments c
                JOIN posts p ON c.post_id = p.post_id
                WHERE c.comment_id = ?
            ");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$comment) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Comment not found']);
                exit;
            }

            // Check if user is the post owner
            if ($comment['post_owner_id'] !== $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only the post owner can approve comments']);
                exit;
            }

            if ($comment['is_approved']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Comment is already approved']);
                exit;
            }

            if ($comment['is_deleted']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Cannot approve deleted comment']);
                exit;
            }

            // Approve the comment
            $stmt = $db->prepare("UPDATE comments SET is_approved = TRUE WHERE comment_id = ?");
            $result = $stmt->execute([$commentId]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Comment approved successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to approve comment']);
            }
            break;

        case 'reject_comment':
            if (!isset($_POST['comment_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Comment ID not provided']);
                exit;
            }

            $commentId = filter_var($_POST['comment_id'], FILTER_SANITIZE_NUMBER_INT);
            
            // Get the comment and associated post info
            $stmt = $db->prepare("
                SELECT c.is_deleted, c.post_id, p.user_id as post_owner_id 
                FROM comments c
                JOIN posts p ON c.post_id = p.post_id
                WHERE c.comment_id = ?
            ");
            $stmt->execute([$commentId]);
            $comment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$comment) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Comment not found']);
                exit;
            }

            // Check if user is the post owner
            if ($comment['post_owner_id'] !== $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only the post owner can reject comments']);
                exit;
            }

            if ($comment['is_deleted']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Comment is already deleted']);
                exit;
            }

            // Reject the comment by marking it as deleted
            $stmt = $db->prepare("UPDATE comments SET is_deleted = TRUE WHERE comment_id = ?");
            $result = $stmt->execute([$commentId]);

            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Comment rejected successfully']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to reject comment']);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (PDOException $e) {
    error_log("Database error in post_interactions.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'A database error occurred']);
} 