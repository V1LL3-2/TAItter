<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'config/translations.php'; // Add this line
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

// Basic stats calculation (fallback if enhanced methods don't exist)
$stats = [
    'total_posts' => count($posts),
    'unique_users' => count(array_unique(array_column($posts, 'user_id'))),
    'followers_count' => 0,
    'first_post_date' => !empty($posts) ? end($posts)['created_at'] : null,
    'last_post_date' => !empty($posts) ? $posts[0]['created_at'] : null,
    'posts_last_24h' => 0,
    'posts_last_week' => 0
];

// Try to get enhanced stats if method exists
if (method_exists($hashtag, 'getHashtagStats')) {
    $stats = $hashtag->getHashtagStats($hashtag->id);
}

// Get related hashtags (empty if method doesn't exist)
$related_hashtags = [];
if (method_exists($hashtag, 'getRelatedHashtags')) {
    $related_hashtags = $hashtag->getRelatedHashtags($hashtag->id, 5);
}

// Get top users (empty if method doesn't exist)
$top_users = [];
if (method_exists($hashtag, 'getTopUsers')) {
    $top_users = $hashtag->getTopUsers($hashtag->id, 3);
}

// Check if user follows this hashtag
$is_followed = false;
if(is_logged_in() && method_exists($hashtag, 'isFollowedByUser')) {
    $is_followed = $hashtag->isFollowedByUser(get_current_user_id(), $hashtag->id);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $default_language; ?>">
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
                    <!-- Hashtag Sidebar -->
                    <aside class="sidebar">
                        <div class="hashtag-card">
                            <div class="hashtag-header">
                                <h2>#<?php echo htmlspecialchars($tag); ?></h2>
                                <p class="hashtag-description">
                                    <?php echo t('posts_tagged_with'); ?> #<?php echo htmlspecialchars($tag); ?>
                                </p>
                                <div class="hashtag-stats">
                                    <div class="stat">
                                        <strong><?php echo number_format($stats['total_posts']); ?></strong>
                                        <span><?php echo t('posts'); ?></span>
                                    </div>
                                    <div class="stat">
                                        <strong><?php echo number_format($stats['unique_users']); ?></strong>
                                        <span><?php echo t('users'); ?></span>
                                    </div>
                                    <?php if($stats['followers_count'] > 0): ?>
                                    <div class="stat">
                                        <strong><?php echo number_format($stats['followers_count']); ?></strong>
                                        <span><?php echo t('followers'); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if($stats['first_post_date']): ?>
                                    <div class="hashtag-timeline">
                                        <p><small><?php echo t('first_used'); ?>: <?php echo date('M j, Y', strtotime($stats['first_post_date'])); ?></small></p>
                                        <?php if($stats['last_post_date']): ?>
                                            <p><small><?php echo t('last_used'); ?>: <?php echo time_ago($stats['last_post_date']); ?></small></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if(is_logged_in()): ?>
                                <div class="hashtag-actions">
                                    <button class="btn btn-primary btn-full follow-hashtag-btn" 
                                            data-hashtag-id="<?php echo $hashtag->id; ?>"
                                            data-followed="<?php echo $is_followed ? 'true' : 'false'; ?>">
                                        <i class="<?php echo $is_followed ? 'fas' : 'far'; ?> fa-plus"></i>
                                        <?php echo $is_followed ? t('following_btn') : t('follow_hashtag'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Activity Stats -->
                        <?php if(isset($stats['posts_last_24h']) && ($stats['posts_last_24h'] > 0 || $stats['posts_last_week'] > 0)): ?>
                            <div class="widget">
                                <h3><?php echo t('recent_activity'); ?></h3>
                                <div class="activity-stats">
                                    <?php if($stats['posts_last_24h'] > 0): ?>
                                        <div class="activity-item">
                                            <span class="activity-count"><?php echo $stats['posts_last_24h']; ?></span>
                                            <span class="activity-label"><?php echo t('posts_today'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if($stats['posts_last_week'] > 0): ?>
                                        <div class="activity-item">
                                            <span class="activity-count"><?php echo $stats['posts_last_week']; ?></span>
                                            <span class="activity-label"><?php echo t('posts_this_week'); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Top Contributors -->
                        <?php if(!empty($top_users)): ?>
                            <div class="widget">
                                <h3><?php echo t('top_contributors'); ?></h3>
                                <div class="top-users">
                                    <?php foreach($top_users as $user): ?>
                                        <div class="user-item">
                                            <div class="user-info">
                                                <div class="avatar">
                                                    <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                                </div>
                                                <div class="user-details">
                                                    <a href="profile.php?username=<?php echo urlencode($user['username']); ?>" class="username">
                                                        @<?php echo htmlspecialchars($user['username']); ?>
                                                    </a>
                                                    <span class="post-count"><?php echo $user['post_count']; ?> <?php echo t('posts'); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Related Hashtags -->
                        <?php if(!empty($related_hashtags)): ?>
                            <div class="widget">
                                <h3><?php echo t('related_hashtags'); ?></h3>
                                <div class="related-hashtags">
                                    <?php foreach($related_hashtags as $related): ?>
                                        <a href="hashtag.php?tag=<?php echo urlencode($related['tag']); ?>" class="related-tag">
                                            #<?php echo htmlspecialchars($related['tag']); ?>
                                            <span class="co-occurrence"><?php echo $related['co_occurrence_count']; ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </aside>

                    <!-- Main Content -->
                    <div class="main-content">
                        <div class="feed-header">
                            <h2>#<?php echo htmlspecialchars($tag); ?></h2>
                            <div class="hashtag-info">
                                <span class="post-count"><?php echo number_format(count($posts)); ?> <?php echo t('recent_posts'); ?></span>
                                <button class="btn btn-outline" onclick="refreshFeed()">
                                    <i class="fas fa-sync-alt"></i>
                                    <?php echo t('refresh'); ?>
                                </button>
                            </div>
                        </div>

                        <div class="posts-container" id="posts-container">
                            <?php if(empty($posts)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-hashtag"></i>
                                    <h3><?php echo t('no_posts_yet'); ?></h3>
                                    <p><?php echo t('be_first_to_post'); ?> #<?php echo htmlspecialchars($tag); ?>!</p>
                                    <?php if(is_logged_in()): ?>
                                        <button class="btn btn-primary" onclick="openPostModal()">
                                            <i class="fas fa-plus"></i>
                                            <?php echo t('create_post'); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <?php foreach($posts as $post_item): ?>
                                    <div class="post" data-post-id="<?php echo $post_item['id']; ?>">
                                        <div class="post-header">
                                            <div class="user-info">
                                                <div class="avatar">
                                                    <?php echo strtoupper(substr($post_item['username'], 0, 1)); ?>
                                                </div>
                                                <div class="user-details">
                                                    <a href="profile.php?username=<?php echo urlencode($post_item['username']); ?>" class="username">
                                                        @<?php echo htmlspecialchars($post_item['username']); ?>
                                                    </a>
                                                    <span class="post-time">
                                                        <?php echo time_ago($post_item['created_at']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php if(is_logged_in() && $post_item['user_id'] != get_current_user_id()): ?>
                                                <button class="btn btn-sm like-user-btn" data-user-id="<?php echo $post_item['user_id']; ?>">
                                                    <i class="far fa-heart"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="post-content">
                                            <?php echo format_post_content($post_item['content']); ?>
                                        </div>
                                        
                                        <div class="post-actions">
                                            <?php if(is_logged_in() && $post_item['user_id'] != get_current_user_id()): ?>
                                                <button class="action-btn like-user-btn" data-user-id="<?php echo $post_item['user_id']; ?>">
                                                    <i class="far fa-heart"></i>
                                                    <span><?php echo t('like_user'); ?></span>
                                                </button>
                                            <?php endif; ?>
                                            <button class="action-btn follow-hashtag-btn" data-hashtags='<?php echo json_encode(extract_hashtags($post_item['content'])); ?>'>
                                                <i class="fas fa-hashtag"></i>
                                                <span><?php echo t('follow_tags'); ?></span>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <!-- Load More Button -->
                                <?php if(count($posts) >= 20): ?>
                                    <div class="load-more-container">
                                        <button class="btn btn-outline btn-full" onclick="loadMorePosts()">
                                            <i class="fas fa-chevron-down"></i>
                                            <?php echo t('load_more'); ?>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Post Modal -->
    <?php if(is_logged_in()): ?>
        <div id="post-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><?php echo t('create_new_post_with'); ?> #<?php echo htmlspecialchars($tag); ?></h3>
                    <button class="close-btn" onclick="closePostModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="post-form">
                        <textarea id="post-content" placeholder="<?php echo t('whats_happening'); ?> #<?php echo htmlspecialchars($tag); ?>? <?php echo t('characters_max'); ?>" maxlength="144">#<?php echo htmlspecialchars($tag); ?> </textarea>
                        <div class="char-count">
                            <span id="char-count"><?php echo strlen($tag) + 2; ?></span>/144
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" onclick="closePostModal()"><?php echo t('cancel'); ?></button>
                    <button class="btn btn-primary" onclick="submitPost()"><?php echo t('post'); ?></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Set current user ID for JavaScript
        window.currentUserId = <?php echo get_current_user_id() ?: 'null'; ?>;
        
        // Initialize character count for pre-filled hashtag
        document.addEventListener('DOMContentLoaded', function() {
            const postContent = document.getElementById('post-content');
            const charCount = document.getElementById('char-count');
            if (postContent && charCount) {
                charCount.textContent = postContent.value.length;
            }
        });
    </script>
</body>
</html>

<?php
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
.language-switcher {
    display: flex;
    gap: 0.5rem;
    margin-left: 1rem;
}

.lang-btn {
    padding: 0.5rem 0.75rem;
    text-decoration: none;
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-sm);
    font-size: 0.9rem;
    font-weight: 500;
    transition: var(--transition);
}

.lang-btn:hover {
    background: var(--background-color);
    color: var(--text-primary);
}

.lang-btn.active {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

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
    margin-bottom: 1.5rem;
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

.hashtag-timeline {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
    text-align: center;
}

.hashtag-timeline p {
    margin-bottom: 0.25rem;
    color: var(--text-muted);
}

.hashtag-actions {
    margin-top: 1rem;
}

.activity-stats {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    background: var(--background-color);
    border-radius: var(--border-radius-sm);
}

.activity-count {
    font-weight: 600;
    color: var(--primary-color);
}

.activity-label {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.top-users .user-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border-color);
}

.top-users .user-item:last-child {
    border-bottom: none;
}

.top-users .user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
}

.top-users .avatar {
    width: 32px;
    height: 32px;
    background: var(--primary-color);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    font-weight: 600;
}

.top-users .user-details {
    flex: 1;
}

.top-users .username {
    font-size: 0.9rem;
    font-weight: 500;
    text-decoration: none;
    color: var(--text-primary);
    display: block;
}

.top-users .username:hover {
    color: var(--primary-color);
}

.top-users .post-count {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.related-hashtags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.related-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: 1rem;
    font-size: 0.9rem;
    font-weight: 500;
    transition: var(--transition);
}

.related-tag:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
}

.related-tag .co-occurrence {
    background: rgba(255, 255, 255, 0.2);
    padding: 0.125rem 0.375rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
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

.load-more-container {
    padding: 2rem;
    text-align: center;
    border-top: 1px solid var(--border-color);
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: var(--border-color);
}

.empty-state h3 {
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
}

.empty-state p {
    margin-bottom: 1.5rem;
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
    
    .hashtag-info {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .related-hashtags {
        justify-content: center;
    }
    
    .activity-stats {
        gap: 0.5rem;
    }
    
    .language-switcher {
        margin-left: 0;
        margin-top: 0.5rem;
    }
    
    .header-content {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>