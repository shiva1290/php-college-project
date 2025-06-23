<?php
require_once 'includes/config.php';

$db = getDB();
if (!$db) {
    die("Database connection failed");
}

$post = null;
$comments = [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['comment_id']) && isset($_POST['action'])) {
        $commentId = $_POST['comment_id'];
        $action = $_POST['action'];
        $postId = $_POST['post_id'];
        $accessToken = $_POST['access_token'];

        // Verify post ownership
        $stmt = $db->prepare("SELECT 1 FROM posts WHERE post_id = ? AND access_token = ? AND is_deleted = FALSE");
        $stmt->execute([$postId, $accessToken]);
        
        if ($stmt->fetch()) {
            try {
                if ($action === 'approve') {
                    $stmt = $db->prepare("UPDATE comments SET is_approved = TRUE WHERE comment_id = ? AND post_id = ?");
                } else if ($action === 'delete') {
                    $stmt = $db->prepare("UPDATE comments SET is_deleted = TRUE WHERE comment_id = ? AND post_id = ?");
                }
                $stmt->execute([$commentId, $postId]);
                header("Location: manage.php?id=" . urlencode($postId) . "&token=" . urlencode($accessToken));
                exit;
            } catch (PDOException $e) {
                $error = "Failed to update comment";
            }
        } else {
            $error = "Invalid access token";
        }
    }
}

if (isset($_GET['id']) && isset($_GET['token'])) {
    $postId = $_GET['id'];
    $accessToken = $_GET['token'];

    // Get post
    $stmt = $db->prepare("
        SELECT * FROM posts 
        WHERE post_id = ? AND access_token = ? AND is_deleted = FALSE
    ");
    $stmt->execute([$postId, $accessToken]);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($post) {
        // Get all comments for the post
        $stmt = $db->prepare("
            SELECT * FROM comments 
            WHERE post_id = ? AND is_deleted = FALSE 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$postId]);
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = "Post not found or invalid access token";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Post - Grey Shot</title>
    <link rel="stylesheet" href="assets/css/style.css">
   
</head>
<body>
    <header>
        <h1>Grey Shot</h1>
        <p class="tagline">Manage Your Post</p>
    </header>

    <main>
        <?php if ($error): ?>
            <div class="error" style="color: red; margin-bottom: 1rem;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!$post && !$error): ?>
            <div class="post-form">
                <h2>Enter Post Details</h2>
                <p>To manage your post, please enter your post ID and access token:</p>
                <form method="GET" action="manage.php">
                    <input type="text" name="id" placeholder="Post ID" required style="width: 100%; margin-bottom: 1rem; padding: 0.5rem;">
                    <input type="text" name="token" placeholder="Access Token" required style="width: 100%; margin-bottom: 1rem; padding: 0.5rem;">
                    <button type="submit">Access Post</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if ($post): ?>
            <article class="post">
                <div class="post-content">
                    <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                </div>
                <div class="post-meta">
                    <time datetime="<?php echo $post['created_at']; ?>">
                        <?php echo date('M j, Y g:i a', strtotime($post['created_at'])); ?>
                    </time>
                </div>
            </article>

            <section class="comments">
                <h2>Manage Comments</h2>
                <?php if (empty($comments)): ?>
                    <p>No comments yet.</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <div class="comment-content">
                                <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                            </div>
                            <div class="comment-meta">
                                <time datetime="<?php echo $comment['created_at']; ?>">
                                    <?php echo date('M j, Y g:i a', strtotime($comment['created_at'])); ?>
                                </time>
                                <span class="<?php echo $comment['is_approved'] ? 'approved' : 'pending'; ?>">
                                    <?php echo $comment['is_approved'] ? 'Approved' : 'Pending'; ?>
                                </span>
                            </div>
                            <?php if (!$comment['is_approved']): ?>
                                <div class="manage-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                        <input type="hidden" name="access_token" value="<?php echo $post['access_token']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="approve-btn">Approve</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                        <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                        <input type="hidden" name="access_token" value="<?php echo $post['access_token']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="delete-btn">Delete</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Grey Shot. A safe space for anonymous sharing.</p>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html> 