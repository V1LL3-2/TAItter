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

// Handle language switching
if (isset($_GET['lang'])) {
    set_language($_GET['lang']);
    // Redirect to remove lang parameter from URL
    $redirect_url = strtok($_SERVER["REQUEST_URI"], '?');
    if (!empty($_GET)) {
        $params = $_GET;
        unset($params['lang']);
        if (!empty($params)) {
            $redirect_url .= '?' . http_build_query($params);
        }
    }
    header("Location: $redirect_url");
    exit;
}

?>
<!DOCTYPE html>
<html lang="<?php echo $default_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TAItter - <?php echo t('welcome_to'); ?> TAItter</title>
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
                            <span><?php echo t('home'); ?></span>
                        </a>
                        <a href="search.php" class="nav-link">
                            <i class="fas fa-search"></i>
                            <span><?php echo t('search'); ?></span>
                        </a>
                        <?php if(is_logged_in()): ?>
                            <a href="profile.php" class="nav-link">
                                <i class="fas fa-user"></i>
                                <span><?php echo t('profile'); ?></span>
                            </a>
                            <a href="settings.php" class="nav-link">
                                <i class="fas fa-cog"></i>
                                <span><?php echo t('settings'); ?></span>
                            </a>
                            <a href="api/auth.php" class="nav-link logout-btn" onclick="logout()">
                                <i class="fas fa-sign-out-alt"></i>
                                <span><?php echo t('logout'); ?></span>
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="nav-link">
                                <i class="fas fa-sign-in-alt"></i>
                                <span><?php echo t('login'); ?></span>
                            </a>
                            <a href="register.php" class="nav-link">
                                <i class="fas fa-user-plus"></i>
                                <span><?php echo t('register'); ?></span>
                            </a>
                        <?php endif; ?>
                    </nav>
                    
                    <!-- Language Switcher -->
                    <div class="language-switcher">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['lang' => 'fi'])); ?>" 
                           class="lang-btn <?php echo $default_language === 'fi' ? 'active' : ''; ?>">FI</a>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['lang' => 'en'])); ?>" 
                           class="lang-btn <?php echo $default_language === 'en' ? 'active' : ''; ?>">EN</a>
                    </div>
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
                                <h3><?php echo t('create_post'); ?></h3>
                                <button class="btn btn-primary btn-full" onclick="openPostModal()">
                                    <i class="fas fa-plus"></i>
                                    <?php echo t('create_post'); ?>
                                </button>
                            </div>
                            
                            <div class="widget">
                                <h3><?php echo t('hashtags'); ?></h3>
                                <div id="trending-hashtags">
                                    <div class="loading"><?php echo t('loading'); ?>...</div>
                                </div>
                            </div>
                            
                            <div class="widget">
                                <h3><?php echo t('users'); ?></h3>
                                <div id="suggested-users">
                                    <div class="loading"><?php echo t('loading'); ?>...</div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="widget">
                                <h3><?php echo t('welcome_to'); ?> TAItter</h3>
                                <p><?php echo $default_language === 'fi' ? 'Sosiaalisen median tulevaisuus on täällä! Liity yhteisöömme ja ala jakaa ajatuksiasi 144 merkissä tai vähemmän.' : 'The future of social media is here! Join our community and start sharing your thoughts in 144 characters or less.'; ?></p>
                                <div class="auth-buttons">
                                    <a href="login.php" class="btn btn-primary"><?php echo t('login'); ?></a>
                                    <a href="register.php" class="btn btn-secondary"><?php echo t('register'); ?></a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </aside>

                    <!-- Main Feed -->
                    <div class="main-content">
                        <div class="feed-header">
                            <h2>
                                <?php if(is_logged_in()): ?>
                                    <?php echo $default_language === 'fi' ? 'Aikajana' : 'Your Timeline'; ?>
                                <?php else: ?>
                                    <?php echo $default_language === 'fi' ? 'Julkinen syöte' : 'Public Feed'; ?>
                                <?php endif; ?>
                            </h2>
                            <button class="btn btn-outline" onclick="refreshFeed()">
                                <i class="fas fa-sync-alt"></i>
                                <?php echo t('refresh'); ?>
                            </button>
                        </div>

                        <div class="posts-container" id="posts-container">
                            <?php if(empty($posts)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-comments"></i>
                                    <h3><?php echo t('no_posts_yet'); ?></h3>
                                    <p><?php echo $default_language === 'fi' ? 'Ole ensimmäinen, joka jakaa jotain!' : 'Be the first to share something!'; ?></p>
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
                                                <span><?php echo t('like_user'); ?></span>
                                            </button>
                                            <button class="action-btn follow-hashtag-btn" data-hashtags='<?php echo json_encode(extract_hashtags($post['content'])); ?>'>
                                                <i class="fas fa-hashtag"></i>
                                                <span><?php echo t('follow_tags'); ?></span>
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
                <h3><?php echo t('create_post'); ?></h3>
                <button class="close-btn" onclick="closePostModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="post-form">
                    <textarea id="post-content" placeholder="<?php echo t('whats_happening'); ?>? <?php echo t('characters_max'); ?>" maxlength="144"></textarea>
                    <div class="char-count">
                        <span id="char-count">0</span>/144
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closePostModal()"><?php echo t('cancel'); ?></button>
                <button class="btn btn-primary" onclick="submitPost()"><?php echo t('post'); ?></button>
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
    global $default_language;
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return t('just_now');
    if ($time < 3600) return floor($time/60) . ' ' . t('minutes_ago');
    if ($time < 86400) return floor($time/3600) . ' ' . t('hours_ago');
    if ($time < 2592000) return floor($time/86400) . ' ' . t('days_ago');
    if ($time < 31536000) return floor($time/2592000) . ' ' . t('months_ago');
    return floor($time/31536000) . ' ' . t('years_ago');
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

<style>
.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 0;
    gap: 1rem;
}

.nav {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    justify-content: center;
}

.language-switcher {
    display: flex;
    gap: 0.25rem;
    margin-left: auto;
    padding: 0.25rem;
    background: var(--background-color);
    border-radius: var(--border-radius-sm);
}

.lang-btn {
    padding: 0.5rem 0.875rem;
    text-decoration: none;
    color: var(--text-secondary);
    border: none;
    border-radius: calc(var(--border-radius-sm) - 2px);
    font-size: 0.875rem;
    font-weight: 600;
    transition: var(--transition);
    min-width: 40px;
    text-align: center;
}

.lang-btn:hover {
    background: rgba(29, 161, 242, 0.1);
    color: var(--primary-color);
}

.lang-btn.active {
    background: var(--primary-color);
    color: white;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
}

@media (max-width: 768px) {
    .header-content {
        flex-wrap: wrap;
    }
    
    .language-switcher {
        margin-left: 0;
        order: 3;
        width: 100%;
        justify-content: center;
        margin-top: 0.5rem;
    }
    
    .nav {
        order: 2;
    }
    
    .logo {
        order: 1;
    }
}
.user-item {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
    gap: 0.75rem;
}

.user-item:last-child {
    border-bottom: none;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 0; /* Important for text overflow */
}

.user-details {
    flex: 1;
    min-width: 0; /* Important for text overflow */
    overflow: hidden; /* Prevents text overflow */
}

.username {
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    color: var(--text-primary);
    display: block;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-description {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.4;
}
</style>