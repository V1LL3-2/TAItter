<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/Post.php';
require_once 'models/Hashtag.php';

$database = new Database();
$db = $database->getConnection();
$post = new Post($db);
$hashtag = new Hashtag($db);

$tag = $_GET['tag'] ?? '';

if(empty($tag)) {
    redirect('index.php');
}

// Get hashtag info
if(!$hashtag->getByTag($tag)) {
    redirect('index.php');
}

// Get posts with this hashtag
$posts = $post->getByHashtag($tag, 20, 0);

// Check if user follows this hashtag
$is_followed = false;
if(is_logged_in()) {
    $is_followed = $hashtag->isFollowedByUser(get_current_user_id(), $hashtag->id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>#<?php echo htmlspecialchars($tag); ?> - TAItter</title>
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
                        <a href="index.php" class="nav-link">
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
                    <!-- Hashtag Sidebar -->
                    <aside class="sidebar">
                        <div class="hashtag-card">
                            <div class="hashtag-header">
                                <h2>#<?php echo htmlspecialchars($tag); ?></h2>
                                <p class="hashtag-description">
                                    Posts tagged with #<?php echo htmlspecialchars($tag); ?>
                                </p>
                                <div class="hashtag-stats">
                                    <span class="stat">
                                        <strong><?php echo count($posts); ?></strong> posts
                                    </span>
                                    <span class="stat">
                                        <strong><?php echo date('M Y', strtotime($hashtag->created_at)); ?></strong> created
                                    </span>
                                </div>
                            </div>
                            
                            <?php if(is_logged_in()): ?>
                                <div class="hashtag-actions">
                                    <button class="btn btn-primary btn-full follow-hashtag-btn" 
                                            data-hashtag-id="<?php echo $hashtag->id; ?>"
                                            data-followed="<?php echo $is_followed ? 'true' : 'false'; ?>">
                                        <i class="<?php echo $is_followed ? 'fas' : 'far'; ?> fa-plus"></i>
                                        <?php echo $is_followed ? 'Following' : 'Follow Hashtag'; ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </aside>

                    <!-- Main Content -->
                    <div class="main-content">
                        <div class="feed-header">
                            <h2>#<?php echo htmlspecialchars($tag); ?></h2>
                            <div class="hashtag-info">
                                <span class="post-count"><?php echo count($posts); ?> posts</span>
                            </div>
                        </div>

                        <div class="posts-container" id="posts-container">
                            <?php if(empty($posts)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-hashtag"></i>
                                    <h3>No posts yet</h3>
                                    <p>Be the first to post with #<?php echo htmlspecialchars($tag); ?>!</p>
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
    // Convert hashtags to clickable links
    $content = preg_replace('/#(\w+)/', '<a href="hashtag.php?tag=$1" class="hashtag">#$1</a>', $content);
    
    // Convert mentions to clickable links
    $content = preg_replace('/@(\w+)/', '<a href="profile.php?username=$1" class="mention">@$1</a>', $content);
    
    return nl2br(htmlspecialchars($content));
}

function extract_hashtags($content) {
    preg_match_all('/#(\w+)/', $content, $matches);
    return $matches[1];
}
?>

<style>
.hashtag-card {
    background: var(--surface-color);
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    margin-bottom: 1.5rem;
}

.hashtag-header {
    text-align: center;
    margin-bottom: 2rem;
}

.hashtag-header h2 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    color: var(--primary-color);
}

.hashtag-description {
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.hashtag-stats {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 1rem;
}

.hashtag-stats .stat {
    text-align: center;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.hashtag-stats .stat strong {
    display: block;
    color: var(--text-primary);
    font-size: 1.2rem;
    margin-bottom: 0.25rem;
}

.hashtag-actions {
    margin-top: 1rem;
}

.feed-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--surface-color);
}

.feed-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.hashtag-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.post-count {
    color: var(--text-muted);
    font-size: 0.9rem;
}

@media (max-width: 768px) {
    .hashtag-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .feed-header {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
}
</style>
