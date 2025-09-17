<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Post.php';
require_once 'models/UserLike.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$post = new Post($db);
$userLike = new UserLike($db);

// Get username from URL
$profile_username = $_GET['username'] ?? null;

if(!$profile_username) {
    if(is_logged_in()) {
        $profile_username = $_SESSION['username'];
    } else {
        redirect('index.php');
    }
}

// Get user profile
if(!$user->getByUsername($profile_username)) {
    redirect('index.php');
}

// Get user stats
$stats = $user->getStats($user->id);

// Get user posts
$posts = $post->getByUser($user->id, 20, 0);

// Check if current user likes this profile
$is_liked = false;
if(is_logged_in() && $user->id != get_current_user_id()) {
    $is_liked = $userLike->isLiked(get_current_user_id(), $user->id);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user->username); ?> - TAItter</title>
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
                            <a href="profile.php" class="nav-link <?php echo $profile_username === $_SESSION['username'] ? 'active' : ''; ?>">
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
                    <!-- Profile Sidebar -->
                    <aside class="sidebar">
                        <div class="profile-card">
                            <div class="profile-header">
                                <div class="profile-avatar">
                                    <?php echo strtoupper(substr($user->username, 0, 1)); ?>
                                </div>
                                <div class="profile-info">
                                    <h2>@<?php echo htmlspecialchars($user->username); ?></h2>
                                    <p class="profile-description">
                                        <?php echo htmlspecialchars($user->description ?: 'No description'); ?>
                                    </p>
                                    <p class="profile-joined">
                                        Joined <?php echo date('F Y', strtotime($user->created_at)); ?>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="profile-stats">
                                <div class="stat">
                                    <span class="stat-number"><?php echo $stats['posts']; ?></span>
                                    <span class="stat-label">Posts</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-number"><?php echo $stats['followers']; ?></span>
                                    <span class="stat-label">Followers</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-number"><?php echo $stats['following']; ?></span>
                                    <span class="stat-label">Following</span>
                                </div>
                            </div>
                            
                            <?php if(is_logged_in() && $user->id != get_current_user_id()): ?>
                                <div class="profile-actions">
                                    <button class="btn btn-primary btn-full like-user-btn" 
                                            data-user-id="<?php echo $user->id; ?>"
                                            data-liked="<?php echo $is_liked ? 'true' : 'false'; ?>">
                                        <i class="<?php echo $is_liked ? 'fas' : 'far'; ?> fa-heart"></i>
                                        <?php echo $is_liked ? 'Liked' : 'Like User'; ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </aside>

                    <!-- Main Content -->
                    <div class="main-content">
                        <div class="feed-header">
                            <h2>@<?php echo htmlspecialchars($user->username); ?>'s Posts</h2>
                        </div>

                        <div class="posts-container" id="posts-container">
                            <?php if(empty($posts)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-comments"></i>
                                    <h3>No posts yet</h3>
                                    <p>@<?php echo htmlspecialchars($user->username); ?> hasn't posted anything yet.</p>
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
.profile-card {
    background: var(--surface-color);
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    margin-bottom: 1.5rem;
}

.profile-header {
    text-align: center;
    margin-bottom: 2rem;
}

.profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 2rem;
    margin: 0 auto 1rem;
}

.profile-info h2 {
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.profile-description {
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    line-height: 1.5;
}

.profile-joined {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.profile-stats {
    display: flex;
    justify-content: space-around;
    margin-bottom: 2rem;
    padding: 1rem 0;
    border-top: 1px solid var(--border-color);
    border-bottom: 1px solid var(--border-color);
}

.stat {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.stat-label {
    font-size: 0.9rem;
    color: var(--text-muted);
}

.profile-actions {
    margin-top: 1rem;
}

@media (max-width: 768px) {
    .profile-stats {
        flex-direction: column;
        gap: 1rem;
    }
    
    .stat {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
}
</style>
