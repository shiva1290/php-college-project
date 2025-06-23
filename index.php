<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Get database connection
$db = getDB();
if (!$db) {
    die("Database connection failed");
}

// Check if user is logged in
$isLoggedIn = isLoggedIn();
$currentUserId = $isLoggedIn ? getCurrentUserId() : null;

// Get current user's profile information if logged in
$currentUserProfile = null;
$currentUsername = null;
$currentUserIcon = null;
if ($isLoggedIn) {
    $currentUserProfile = getUserProfile($currentUserId);
    if ($currentUserProfile) {
        $currentUsername = $currentUserProfile['username'];
        $currentUserIcon = $currentUserProfile['icon_seed'];
    }
}

// Get posts for the feed with user info and interaction counts
$stmt = $db->prepare("
    SELECT 
        p.post_id,
        p.user_id,
        p.content,
        p.created_at,
        p.upvotes,
        u.username,
        u.icon_seed,
        (SELECT COUNT(*) FROM reactions WHERE post_id = p.post_id AND reaction_type = 'relate') as relate_count,
        (SELECT COUNT(*) FROM reactions WHERE post_id = p.post_id AND reaction_type = 'needed') as needed_count,
        (SELECT COUNT(*) FROM reactions WHERE post_id = p.post_id AND reaction_type = 'thanks') as thanks_count,
        CASE WHEN rh.user_id IS NOT NULL THEN 1 ELSE 0 END as is_read,
        CASE WHEN uv.user_id IS NOT NULL THEN 1 ELSE 0 END as has_upvoted,
        CASE WHEN r_relate.user_id IS NOT NULL THEN 1 ELSE 0 END as has_reacted_relate,
        CASE WHEN r_needed.user_id IS NOT NULL THEN 1 ELSE 0 END as has_reacted_needed,
        CASE WHEN r_thanks.user_id IS NOT NULL THEN 1 ELSE 0 END as has_reacted_thanks
    FROM posts p
    JOIN users u ON p.user_id = u.user_id
    LEFT JOIN reading_history rh ON p.post_id = rh.post_id AND rh.user_id = ?
    LEFT JOIN upvotes uv ON p.post_id = uv.post_id AND uv.user_id = ?
    LEFT JOIN reactions r_relate ON p.post_id = r_relate.post_id AND r_relate.user_id = ? AND r_relate.reaction_type = 'relate'
    LEFT JOIN reactions r_needed ON p.post_id = r_needed.post_id AND r_needed.user_id = ? AND r_needed.reaction_type = 'needed'
    LEFT JOIN reactions r_thanks ON p.post_id = r_thanks.post_id AND r_thanks.user_id = ? AND r_thanks.reaction_type = 'thanks'
    WHERE p.is_deleted = FALSE 
    ORDER BY p.created_at DESC 
    LIMIT 20
");
$stmt->execute(array_fill(0, 5, $currentUserId ?? null));
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get approved comments for each post
foreach ($posts as &$post) {
    $stmt = $db->prepare("
        SELECT 
            c.comment_id,
            c.content,
            c.created_at,
            u.username,
            u.icon_seed
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.user_id
        WHERE c.post_id = ? AND c.is_approved = TRUE AND c.is_deleted = FALSE 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$post['post_id']]);
    $post['comments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Check if user can post today
$canPostToday = $isLoggedIn && canPostToday($currentUserId);
$needsToReadMore = $isLoggedIn && !hasReadEnough($currentUserId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grey Shot - Share your truth</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo filemtime('assets/css/style.css'); ?>">
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar">
            <nav class="sidebar-nav">
                <div class="nav-item active">
                    <svg viewBox="0 0 24 24" class="nav-icon">
                        <path fill="currentColor" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/>
                    </svg>
                    <span>Dashboard</span>
                </div>
                <?php if ($isLoggedIn): ?>
                <div class="user-profile">
                    <div class="user-icon" style="background-image: url('https://api.dicebear.com/7.x/shapes/svg?seed=<?php echo htmlspecialchars($currentUserIcon); ?>')"></div>
                    <span class="username"><?php echo htmlspecialchars($currentUsername); ?></span>
                    <a href="logout.php" class="logout-btn">Logout</a>
                </div>
                <?php else: ?>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-secondary">Register</a>
                </div>
                <?php endif; ?>
            </nav>

            <?php if ($isLoggedIn): ?>
            <div class="user-stats">
                <h2>Your Stats</h2>
                <?php
                // Get user stats
                $statsStmt = $db->prepare("
                    SELECT 
                        (SELECT COUNT(*) FROM posts WHERE user_id = ?) as total_posts,
                        (SELECT COUNT(*) FROM reactions r JOIN posts p ON r.post_id = p.post_id WHERE p.user_id = ?) as total_reactions,
                        (SELECT COUNT(*) FROM reading_history WHERE user_id = ?) as posts_read,
                        (SELECT COUNT(*) FROM comments c JOIN posts p ON c.post_id = p.post_id WHERE p.user_id = ? AND c.is_approved = TRUE) as approved_comments
                ");
                $statsStmt->execute(array_fill(0, 4, $currentUserId));
                $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <div class="stats-section">
                    <div class="stat-card">
                        <svg viewBox="0 0 24 24" class="stat-icon">
                            <path fill="currentColor" d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zm-5.04-6.71l-2.75 3.54-1.96-2.36L6.5 17h11l-3.54-4.71z"/>
                        </svg>
                        <div class="stat-number"><?php echo $stats['total_posts']; ?></div>
                        <div class="stat-label">Posts</div>
                    </div>
                    <div class="stat-card">
                        <svg viewBox="0 0 24 24" class="stat-icon">
                            <path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                        </svg>
                        <div class="stat-number"><?php echo $stats['total_reactions']; ?></div>
                        <div class="stat-label">Reactions</div>
                    </div>
                    <div class="stat-card">
                        <svg viewBox="0 0 24 24" class="stat-icon">
                            <path fill="currentColor" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                        <div class="stat-number"><?php echo $stats['posts_read']; ?></div>
                        <div class="stat-label">Posts Read</div>
                    </div>
                    <div class="stat-card">
                        <svg viewBox="0 0 24 24" class="stat-icon">
                            <path fill="currentColor" d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
                        </svg>
                        <div class="stat-number"><?php echo $stats['approved_comments']; ?></div>
                        <div class="stat-label">Comments</div>
                    </div>
                </div>
            </div>

            <div class="recent-activity">
                <h2>Recent Activity</h2>
                <?php
                // Get recent reactions on user's posts
                $activityStmt = $db->prepare("
                    SELECT 
                        r.reaction_type,
                        r.created_at,
                        u.username,
                        u.icon_seed,
                        p.post_id,
                        SUBSTRING(p.content, 1, 50) as post_preview,
                        DATE(r.created_at) as activity_date
                    FROM reactions r
                    JOIN posts p ON r.post_id = p.post_id
                    JOIN users u ON r.user_id = u.user_id
                    WHERE p.user_id = ?
                    ORDER BY r.created_at DESC
                    LIMIT 10
                ");
                $activityStmt->execute([$currentUserId]);
                $activities = $activityStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($activities)): ?>
                    <div class="empty-state">
                        <svg viewBox="0 0 24 24" class="empty-icon">
                            <path fill="currentColor" d="M13.5 8.5c0 .83-.67 1.5-1.5 1.5s-1.5-.67-1.5-1.5S11.17 7 12 7s1.5.67 1.5 1.5zM13 17v-4h-2v4h2zm-1-9c-1.65 0-3 1.35-3 3v4c0 1.65 1.35 3 3 3s3-1.35 3-3v-4c0-1.65-1.35-3-3-3zm0-6C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z"/>
                        </svg>
                        <p>No recent activity yet</p>
                    </div>
                <?php else:
                    $currentDate = '';
                    foreach ($activities as $activity):
                        $activityDate = date('Y-m-d', strtotime($activity['activity_date']));
                        if ($activityDate !== $currentDate):
                            if ($currentDate !== '') echo '</div>'; // Close previous activity group
                            $currentDate = $activityDate;
                            $dateDisplay = date('F j', strtotime($activityDate));
                            if ($activityDate === date('Y-m-d')) {
                                $dateDisplay = 'Today';
                            } elseif ($activityDate === date('Y-m-d', strtotime('-1 day'))) {
                                $dateDisplay = 'Yesterday';
                            }
                ?>
                            <div class="activity-date"><?php echo $dateDisplay; ?></div>
                            <div class="activity-group">
                <?php endif; ?>
                        <div class="activity-item">
                            <div class="activity-user">
                                <div class="user-icon tiny" style="background-image: url('https://api.dicebear.com/7.x/shapes/svg?seed=<?php echo htmlspecialchars($activity['icon_seed']); ?>')"></div>
                                <span class="username"><?php echo htmlspecialchars($activity['username']); ?></span>
                            </div>
                            <div class="activity-content">
                                <span class="activity-action">
                                    <?php 
                                    switch($activity['reaction_type']) {
                                        case 'relate':
                                            echo 'related to';
                                            break;
                                        case 'needed':
                                            echo 'needed';
                                            break;
                                        case 'thanks':
                                            echo 'thanked you for';
                                            break;
                                    }
                                    ?>
                                </span>
                                <a href="#post-<?php echo $activity['post_id']; ?>" class="activity-post-link">
                                    "<?php echo htmlspecialchars($activity['post_preview']); ?>..."
                                </a>
                            </div>
                            <div class="activity-meta">
                                <?php echo date('g:i A', strtotime($activity['created_at'])); ?>
                            </div>
                        </div>
                <?php 
                    endforeach;
                    if (!empty($activities)) echo '</div>'; // Close last activity group
                endif; 
                ?>
            </div>
            <?php endif; ?>
        </aside>

        <div class="main-content">
            <header>
                <h1>Grey Shot</h1>
                <p class="tagline">Share one truth, discover many.</p>
                <nav>
                    <?php if ($isLoggedIn): ?>
                        <?php 
                        $profile = getUserProfile($currentUserId);
                        if ($profile): 
                        ?>
                        <div class="user-info">
                            <div class="user-icon" style="background-image: url('https://api.dicebear.com/7.x/shapes/svg?seed=<?php echo htmlspecialchars($profile['icon_seed']); ?>')"></div>
                            <span><?php echo htmlspecialchars($profile['username']); ?></span>
                            <a href="logout.php" class="nav-link">Logout</a>
                        </div>
                        <?php else: ?>
                        <div class="user-info">
                            <a href="logout.php" class="nav-link">Logout</a>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="nav-link">Login / Register</a>
                    <?php endif; ?>
                </nav>
            </header>

            <main class="main-content">
                <button id="refresh-btn" onclick="refreshPosts()" style="position: fixed; top: 20px; right: 20px; z-index: 1000; background: var(--color-primary); color: white; border: none; border-radius: 50px; padding: 10px 15px; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.3);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z"/>
                    </svg>
                    Refresh
                </button>
                <?php if ($isLoggedIn): ?>
                    <?php if ($needsToReadMore): ?>
                        <div class="notice-card">
                            <svg viewBox="0 0 24 24" class="notice-icon">
                                <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                            </svg>
                            <p>Read more posts to unlock daily sharing!</p>
                        </div>
                    <?php elseif (!$canPostToday): ?>
                        <div class="notice-card">
                            <svg viewBox="0 0 24 24" class="notice-icon">
                                <path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                            </svg>
                            <p>You've already shared your truth today. Come back tomorrow!</p>
                        </div>
                    <?php else: ?>
                        <div class="post-form-container">
                            <h2>Daily Truth Shared</h2>
                            <form id="postForm" action="includes/submit_post.php" method="POST">
                                <div class="form-group">
                                    <textarea name="content" required placeholder="Share one truth, discover many..." maxlength="500"></textarea>
                                    <div class="char-count"><span>0</span>/500</div>
                                </div>
                                <button type="submit" class="submit-btn">
                                    <span class="button-text">Share Truth</span>
                                    <span class="loading-indicator"></span>
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="welcome-card">
                        <h1>Welcome to Grey Shot</h1>
                        <p>Share one truth, discover many. Join our community to start sharing.</p>
                        <div class="auth-buttons">
                            <a href="login.php" class="btn btn-primary">Login</a>
                            <a href="register.php" class="btn btn-secondary">Register</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="posts-container">
                    <?php if (empty($posts)): ?>
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" class="empty-icon">
                                <path fill="currentColor" d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-7 12h-2v-2h2v2zm0-4h-2V6h2v4z"/>
                            </svg>
                            <h3>No posts yet</h3>
                            <p>Be the first to share your truth with the community!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                        <article class="post-card" id="post-<?php echo $post['post_id']; ?>">
                            <div class="post-header">
                                <div class="post-author">
                                    <div class="user-icon" style="background-image: url('https://api.dicebear.com/7.x/shapes/svg?seed=<?php echo htmlspecialchars($post['icon_seed']); ?>')"></div>
                                    <div class="author-info">
                                        <span class="username"><?php echo htmlspecialchars($post['username']); ?></span>
                                        <span class="post-time"><?php echo date('M j, g:i A', strtotime($post['created_at'])); ?></span>
                                    </div>
                                </div>
                                <?php if ($post['user_id'] === $currentUserId): ?>
                                <div class="post-actions">
                                    <button class="action-btn delete-post" data-post-id="<?php echo $post['post_id']; ?>">
                                        <svg viewBox="0 0 24 24" class="action-icon">
                                            <path fill="currentColor" d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                        </svg>
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="post-content">
                                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
                            </div>
                            <div class="post-interactions">
                                <button class="interaction-btn upvote-btn <?php echo $post['has_upvoted'] ? 'active' : ''; ?>" 
                                        data-post-id="<?php echo $post['post_id']; ?>"
                                        data-tooltip="Upvote this post">
                                    <svg viewBox="0 0 24 24" class="interaction-icon">
                                        <path fill="currentColor" d="M4 14h4v7a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-7h4a1.001 1.001 0 0 0 .781-1.625l-8-10c-.381-.475-1.181-.475-1.562 0l-8 10A1.001 1.001 0 0 0 4 14z"/>
                                    </svg>
                                    <span class="interaction-count"><?php echo $post['upvotes']; ?></span>
                                </button>
                                <button class="interaction-btn relate-btn <?php echo $post['has_reacted_relate'] ? 'active' : ''; ?>"
                                        data-post-id="<?php echo $post['post_id']; ?>"
                                        data-type="relate"
                                        data-tooltip="I relate to this">
                                    <svg viewBox="0 0 24 24" class="interaction-icon">
                                        <path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                    <span class="interaction-count"><?php echo $post['relate_count']; ?></span>
                                </button>
                                <button class="interaction-btn needed-btn <?php echo $post['has_reacted_needed'] ? 'active' : ''; ?>"
                                        data-post-id="<?php echo $post['post_id']; ?>"
                                        data-type="needed"
                                        data-tooltip="I needed to hear this">
                                    <svg viewBox="0 0 24 24" class="interaction-icon">
                                        <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                    </svg>
                                    <span class="interaction-count"><?php echo $post['needed_count']; ?></span>
                                </button>
                                <button class="interaction-btn thanks-btn <?php echo $post['has_reacted_thanks'] ? 'active' : ''; ?>"
                                        data-post-id="<?php echo $post['post_id']; ?>"
                                        data-type="thanks"
                                        data-tooltip="Thank you for sharing">
                                    <svg viewBox="0 0 24 24" class="interaction-icon">
                                        <path fill="currentColor" d="M21.5 4l-9.97 9.97-4.03-4.03-1.41 1.41 5.44 5.44 11.38-11.38z"/>
                                    </svg>
                                    <span class="interaction-count"><?php echo $post['thanks_count']; ?></span>
                                </button>
                                <button class="interaction-btn comment-btn" data-post-id="<?php echo $post['post_id']; ?>">
                                    <svg viewBox="0 0 24 24" class="interaction-icon">
                                        <path fill="currentColor" d="M21.99 4c0-1.1-.89-2-1.99-2H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14l4 4-.01-18zM18 14H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"/>
                                    </svg>
                                    <span class="interaction-count"><?php echo count($post['comments']); ?></span>
                                </button>
                            </div>
                            <div class="comments">
                                <?php if (empty($post['comments'])): ?>
                                <div class="empty-state">
                                    <p>No comments yet. Be the first to share your thoughts!</p>
                                </div>
                                <?php else: foreach ($post['comments'] as $comment): ?>
                                <div class="comment">
                                    <div class="comment-header">
                                        <div class="comment-author">
                                            <div class="user-icon tiny" style="background-image: url('https://api.dicebear.com/7.x/shapes/svg?seed=<?php echo htmlspecialchars($comment['icon_seed']); ?>')"></div>
                                            <span class="username"><?php echo htmlspecialchars($comment['username']); ?></span>
                                        </div>
                                        <span class="comment-time"><?php echo date('M j, g:i A', strtotime($comment['created_at'])); ?></span>
                                    </div>
                                    <div class="comment-content">
                                        <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                                    </div>
                                </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </main>
        </div>

        <aside class="right-sidebar">
            <?php if ($isLoggedIn): ?>
            <div class="pending-comments">
                <h2>Pending Comments</h2>
                <div class="comments-list">
                    <?php
                    $stmt = $db->prepare("
                        SELECT 
                            c.comment_id,
                            c.content,
                            c.created_at,
                            c.post_id,
                            u.username,
                            u.icon_seed,
                            p.content as post_content
                        FROM comments c
                        LEFT JOIN users u ON c.user_id = u.user_id
                        LEFT JOIN posts p ON c.post_id = p.post_id
                        WHERE c.is_approved = FALSE 
                        AND c.is_deleted = FALSE 
                        AND p.user_id = ?
                        ORDER BY c.created_at DESC
                        LIMIT 10
                    ");
                    $stmt->execute([$currentUserId]);
                    $pendingComments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($pendingComments as $comment): ?>
                    <div class="pending-comment-card" data-comment-id="<?php echo htmlspecialchars($comment['comment_id']); ?>">
                        <div class="comment-author">
                            <div class="user-icon tiny" style="background-image: url('https://api.dicebear.com/7.x/shapes/svg?seed=<?php echo htmlspecialchars($comment['icon_seed']); ?>')"></div>
                            <span><?php echo htmlspecialchars($comment['username']); ?></span>
                        </div>
                        <div class="comment-content">
                            <?php echo nl2br(htmlspecialchars($comment['content'])); ?>
                        </div>
                        <div class="comment-context">
                            <strong>On post:</strong>
                            <p><?php echo substr(htmlspecialchars($comment['post_content']), 0, 100) . '...'; ?></p>
                        </div>
                        <div class="comment-actions">
                            <button class="approve-btn" onclick="approveComment('<?php echo htmlspecialchars($comment['comment_id']); ?>')">
                                <svg viewBox="0 0 24 24" class="action-icon">
                                    <path fill="currentColor" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                                </svg>
                                Approve
                            </button>
                            <button class="reject-btn" onclick="rejectComment('<?php echo htmlspecialchars($comment['comment_id']); ?>')">
                                <svg viewBox="0 0 24 24" class="action-icon">
                                    <path fill="currentColor" d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
                                </svg>
                                Reject
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="trending-posts">
                <h2>
                    <svg viewBox="0 0 24 24">
                        <path fill="currentColor" d="M17.09 4.56c-.7-1.03-1.5-1.99-2.4-2.85-.35-.34-.94-.02-.84.46.19.94.39 2.18.39 3.29 0 2.06-1.35 3.73-3.41 3.73-1.54 0-2.8-.93-3.35-2.26-.1-.2-.14-.32-.2-.54-.11-.42-.66-.55-.9-.18-.18.27-.35.54-.51.83C4.68 9.08 4 11.46 4 14c0 4.42 3.58 8 8 8s8-3.58 8-8c0-3.49-1.08-6.73-2.91-9.44zM11.71 19c-1.78 0-3.22-1.4-3.22-3.14 0-1.62 1.05-2.76 2.81-3.12 1.47-.3 2.98-.93 4.03-1.92.28-.26.74-.14.82.23.23 1.02.35 2.08.35 3.15 0 2.65-2.15 4.8-4.79 4.8z"/>
                    </svg>
                    Trending Posts
                </h2>
                <?php
                // Get trending posts (posts with most reactions in last 24 hours)
                $trendingStmt = $db->prepare("
                    SELECT 
                        p.content,
                        u.username,
                        COUNT(DISTINCT r.reaction_id) as reaction_count,
                        COUNT(DISTINCT c.comment_id) as comment_count
                    FROM posts p
                    JOIN users u ON p.user_id = u.user_id
                    LEFT JOIN reactions r ON p.post_id = r.post_id AND r.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    LEFT JOIN comments c ON p.post_id = c.post_id AND c.is_approved = TRUE
                    WHERE p.is_deleted = FALSE
                    GROUP BY p.post_id
                    ORDER BY reaction_count DESC, comment_count DESC
                    LIMIT 5
                ");
                $trendingStmt->execute();
                $trendingPosts = $trendingStmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="trending-list">
                    <?php foreach ($trendingPosts as $post): ?>
                    <div class="trending-post-item">
                        <div class="trending-post-content">
                            <?php echo substr(htmlspecialchars($post['content']), 0, 100) . '...'; ?>
                        </div>
                        <div class="trending-post-meta">
                            <span class="author"><?php echo htmlspecialchars($post['username']); ?></span>
                            <div class="trending-post-stats">
                                <span><?php echo $post['reaction_count']; ?> reactions</span>
                                <span>•</span>
                                <span><?php echo $post['comment_count']; ?> comments</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="active-users">
                <h2>
                    <svg viewBox="0 0 24 24">
                        <path fill="currentColor" d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
                    </svg>
                    Most Active Users
                </h2>
                <?php
                // Get most active users based on reactions and comments
                $activeUsersStmt = $db->prepare("
                    SELECT 
                        u.user_id,
                        u.username,
                        u.icon_seed,
                        COUNT(DISTINCT r.reaction_id) + COUNT(DISTINCT c.comment_id) as interaction_count
                    FROM users u
                    LEFT JOIN reactions r ON u.user_id = r.user_id AND r.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                    LEFT JOIN comments c ON u.user_id = c.user_id AND c.created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY u.user_id
                    HAVING interaction_count > 0
                    ORDER BY interaction_count DESC
                    LIMIT 5
                ");
                $activeUsersStmt->execute();
                $activeUsers = $activeUsersStmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="active-users-list">
                    <?php foreach ($activeUsers as $user): ?>
                    <div class="active-user-item">
                        <div class="user-info">
                            <div class="user-icon tiny" style="background-image: url('https://api.dicebear.com/7.x/shapes/svg?seed=<?php echo htmlspecialchars($user['icon_seed']); ?>')"></div>
                            <span class="username"><?php echo htmlspecialchars($user['username']); ?></span>
                        </div>
                        <span class="interaction-count"><?php echo $user['interaction_count']; ?> interactions</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Grey Shot. Share your truth, find your light.</p>
    </footer>

    <script src="assets/js/main.js?v=<?php echo filemtime('assets/js/main.js'); ?>"></script>
</body>
</html> 