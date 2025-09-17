<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Post.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$post = new Post($db);

// Get posts for display
$posts = [];
if(is_logged_in()) {
    $posts = $post->getTimeline(get_current_user_id(), 20, 0);
} else {
    $posts = $post->getAll(20, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TAItter - The Future of Social Media</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <div class="logo">
                        <i class="fas fa-bolt"></i>
                        <span>TAItter</span>
                    </div>
                    
                    <nav class="nav">
                        <a href="index.php" class="nav-link active">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                        <a href="search.php" class="nav-link">
                            <i class="fas fa-search"></i>
                            <span>Search</span>
                        </a>
                        <?php if(is_logged_in()): ?>
                            <a href="profile.php" class="nav-link">
                                <i class="fas fa-user"></i>
                                <span>Profile</span>
                            </a>
                            <a href="settings.php" class="nav-link">
                                <i class="fas fa-cog"></i>
                                <span>Settings</span>
                            </a>
                            <a href="api/auth.php" class="nav-link logout-btn" onclick="logout()">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Logout</span>
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="nav-link">
                                <i class="fas fa-sign-in-alt"></i>
                                <span>Login</span>
                            </a>
                            <a href="register.php" class="nav-link">
                                <i class="fas fa-user-plus"></i>
                                <span>Register</span>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main">
            <div class="container">
                <div class="content-grid">
                    <!-- Sidebar -->
                    <aside class="sidebar">
                        <?php if(is_logged_in()): ?>
                            <div class="widget">
                                <h3>Quick Actions</h3>
                                <button class="btn btn-primary btn-full" onclick="openPostModal()">
                                    <i class="fas fa-plus"></i>
                                    New Post
                                </button>
                            </div>
                            
                            <div class="widget">
                                <h3>Trending Hashtags</h3>
                                <div id="trending-hashtags">
                                    <div class="loading">Loading...</div>
                                </div>
                            </div>
                            
                            <div class="widget">
                                <h3>Suggested Users</h3>
                                <div id="suggested-users">
                                    <div class="loading">Loading...</div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="widget">
                                <h3>Welcome to TAItter</h3>
                                <p>The future of social media is here! Join our community and start sharing your thoughts in 144 characters or less.</p>
                                <div class="auth-buttons">
                                    <a href="login.php" class="btn btn-primary">Login</a>
                                    <a href="register.php" class="btn btn-secondary">Register</a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </aside>

                    <!-- Main Feed -->
                    <div class="main-content">
                        <div class="feed-header">
                            <h2>
                                <?php if(is_logged_in()): ?>
                                    Your Timeline
                                <?php else: ?>
                                    Public Feed
                                <?php endif; ?>
                            </h2>
                            <button class="btn btn-outline" onclick="refreshFeed()">
                                <i class="fas fa-sync-alt"></i>
                                Refresh
                            </button>
                        </div>

                        <div class="posts-container" id="posts-container">
                            <?php if(empty($posts)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-comments"></i>
                                    <h3>No posts yet</h3>
                                    <p>Be the first to share something!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($posts as $post): ?>
                                    <div class="post" data-post-id="<?php echo $post['id']; ?>">
                                        <div class="post-header">
                                            <div class="user-info">
                                                <div class="avatar">
                                                    <?php echo strtoupper(substr($post['username'], 0, 1)); ?>
                                                </div>
                                                <div class="user-details">
                                                    <a href="profile.php?username=<?php echo urlencode($post['username']); ?>" class="username">
                                                        @<?php echo htmlspecialchars($post['username']); ?>
                                                    </a>
                                                    <span class="post-time">
                                                        <?php echo time_ago($post['created_at']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php if(is_logged_in() && $post['user_id'] != get_current_user_id()): ?>
                                                <button class="btn btn-sm like-user-btn" data-user-id="<?php echo $post['user_id']; ?>">
                                                    <i class="far fa-heart"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="post-content">
                                            <?php echo format_post_content($post['content']); ?>
                                        </div>
                                        
                                        <div class="post-actions">
                                            <button class="action-btn like-user-btn" data-user-id="<?php echo $post['user_id']; ?>">
                                                <i class="far fa-heart"></i>
                                                <span>Like User</span>
                                            </button>
                                            <button class="action-btn follow-hashtag-btn" data-hashtags='<?php echo json_encode(extract_hashtags($post['content'])); ?>'>
                                                <i class="fas fa-hashtag"></i>
                                                <span>Follow Tags</span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Post Modal -->
    <div id="post-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Create New Post</h3>
                <button class="close-btn" onclick="closePostModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="post-form">
                    <textarea id="post-content" placeholder="What's happening? (144 characters max)" maxlength="144"></textarea>
                    <div class="char-count">
                        <span id="char-count">0</span>/144
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closePostModal()">Cancel</button>
                <button class="btn btn-primary" onclick="submitPost()">Post</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Set current user ID for JavaScript
        window.currentUserId = <?php echo get_current_user_id() ?: 'null'; ?>;
    </script>
</body>
</html>

<?php
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . 'm ago';
    if ($time < 86400) return floor($time/3600) . 'h ago';
    if ($time < 2592000) return floor($time/86400) . 'd ago';
    if ($time < 31536000) return floor($time/2592000) . 'mo ago';
    return floor($time/31536000) . 'y ago';
}

function format_post_content($content) {
    // First escape HTML to prevent XSS
    $content = htmlspecialchars($content);
    
    // Convert hashtags to clickable links
    $content = preg_replace('/#(\w+)/', '<a href="hashtag.php?tag=$1" class="hashtag">#$1</a>', $content);
    
    // Convert mentions to clickable links
    $content = preg_replace('/@(\w+)/', '<a href="profile.php?username=$1" class="mention">@$1</a>', $content);
    
    return nl2br($content);
}

function extract_hashtags($content) {
    preg_match_all('/#(\w+)/', $content, $matches);
    return $matches[1];
}
?>
