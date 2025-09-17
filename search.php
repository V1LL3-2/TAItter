<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Post.php';
require_once 'models/Hashtag.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$post = new Post($db);
$hashtag = new Hashtag($db);

$search_query = $_GET['q'] ?? '';
$search_type = $_GET['type'] ?? 'all'; // all, users, posts, hashtags

$users = [];
$posts = [];
$hashtags = [];

if(!empty($search_query)) {
    switch($search_type) {
        case 'users':
            $users = $user->search($search_query, 20);
            break;
        case 'posts':
            $posts = $post->search($search_query, 20, 0);
            break;
        case 'hashtags':
            $hashtags = $hashtag->search($search_query, 20);
            break;
        case 'all':
        default:
            $users = $user->search($search_query, 10);
            $posts = $post->search($search_query, 10, 0);
            $hashtags = $hashtag->search($search_query, 10);
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - TAItter</title>
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
                        <a href="search.php" class="nav-link active">
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
                <div class="search-container">
                    <!-- Search Header -->
                    <div class="search-header">
                        <h1>Search TAItter</h1>
                        <p>Find users, posts, and hashtags</p>
                    </div>

                    <!-- Search Form -->
                    <div class="search-form-container">
                        <form method="GET" class="search-form">
                            <div class="search-input-group">
                                <input type="text" name="q" class="search-input" 
                                       placeholder="Search for users, posts, or hashtags..." 
                                       value="<?php echo htmlspecialchars($search_query); ?>" required>
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            
                            <div class="search-filters">
                                <label class="filter-option">
                                    <input type="radio" name="type" value="all" 
                                           <?php echo $search_type === 'all' ? 'checked' : ''; ?>>
                                    <span>All</span>
                                </label>
                                <label class="filter-option">
                                    <input type="radio" name="type" value="users" 
                                           <?php echo $search_type === 'users' ? 'checked' : ''; ?>>
                                    <span>Users</span>
                                </label>
                                <label class="filter-option">
                                    <input type="radio" name="type" value="posts" 
                                           <?php echo $search_type === 'posts' ? 'checked' : ''; ?>>
                                    <span>Posts</span>
                                </label>
                                <label class="filter-option">
                                    <input type="radio" name="type" value="hashtags" 
                                           <?php echo $search_type === 'hashtags' ? 'checked' : ''; ?>>
                                    <span>Hashtags</span>
                                </label>
                            </div>
                        </form>
                    </div>

                    <!-- Search Results -->
                    <?php if(!empty($search_query)): ?>
                        <div class="search-results">
                            <div class="results-header">
                                <h2>Search Results for "<?php echo htmlspecialchars($search_query); ?>"</h2>
                                <span class="results-count">
                                    <?php 
                                    $total_results = count($users) + count($posts) + count($hashtags);
                                    echo $total_results . ' result' . ($total_results !== 1 ? 's' : '');
                                    ?>
                                </span>
                            </div>

                            <!-- Users Results -->
                            <?php if(($search_type === 'all' || $search_type === 'users') && !empty($users)): ?>
                                <div class="results-section">
                                    <h3><i class="fas fa-users"></i> Users (<?php echo count($users); ?>)</h3>
                                    <div class="users-grid">
                                        <?php foreach($users as $user_result): ?>
                                            <div class="user-card">
                                                <div class="user-avatar">
                                                    <?php echo strtoupper(substr($user_result['username'], 0, 1)); ?>
                                                </div>
                                                <div class="user-info">
                                                    <a href="profile.php?username=<?php echo urlencode($user_result['username']); ?>" 
                                                       class="user-name">@<?php echo htmlspecialchars($user_result['username']); ?></a>
                                                    <p class="user-description">
                                                        <?php echo htmlspecialchars($user_result['description'] ?: 'No description'); ?>
                                                    </p>
                                                    <span class="user-joined">
                                                        Joined <?php echo date('M Y', strtotime($user_result['created_at'])); ?>
                                                    </span>
                                                </div>
                                                <?php if(is_logged_in() && $user_result['id'] != get_current_user_id()): ?>
                                                    <button class="btn btn-sm like-user-btn" data-user-id="<?php echo $user_result['id']; ?>">
                                                        <i class="far fa-heart"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Posts Results -->
                            <?php if(($search_type === 'all' || $search_type === 'posts') && !empty($posts)): ?>
                                <div class="results-section">
                                    <h3><i class="fas fa-comments"></i> Posts (<?php echo count($posts); ?>)</h3>
                                    <div class="posts-container">
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
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Hashtags Results -->
                            <?php if(($search_type === 'all' || $search_type === 'hashtags') && !empty($hashtags)): ?>
                                <div class="results-section">
                                    <h3><i class="fas fa-hashtag"></i> Hashtags (<?php echo count($hashtags); ?>)</h3>
                                    <div class="hashtags-grid">
                                        <?php foreach($hashtags as $hashtag_result): ?>
                                            <div class="hashtag-card">
                                                <a href="hashtag.php?tag=<?php echo urlencode($hashtag_result['tag']); ?>" 
                                                   class="hashtag-link">#<?php echo htmlspecialchars($hashtag_result['tag']); ?></a>
                                                <span class="hashtag-count"><?php echo $hashtag_result['post_count']; ?> posts</span>
                                                <?php if(is_logged_in()): ?>
                                                    <button class="btn btn-sm follow-hashtag-btn" data-hashtag-id="<?php echo $hashtag_result['id']; ?>">
                                                        <i class="far fa-plus"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- No Results -->
                            <?php if(empty($users) && empty($posts) && empty($hashtags)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-search"></i>
                                    <h3>No results found</h3>
                                    <p>Try searching with different keywords or check your spelling.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Search Suggestions -->
                        <div class="search-suggestions">
                            <h3>Popular Searches</h3>
                            <div class="suggestion-tags">
                                <a href="?q=tech&type=hashtags" class="suggestion-tag">#tech</a>
                                <a href="?q=design&type=hashtags" class="suggestion-tag">#design</a>
                                <a href="?q=programming&type=hashtags" class="suggestion-tag">#programming</a>
                                <a href="?q=startup&type=hashtags" class="suggestion-tag">#startup</a>
                                <a href="?q=innovation&type=hashtags" class="suggestion-tag">#innovation</a>
                            </div>
                        </div>
                    <?php endif; ?>
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
.search-container {
    max-width: 800px;
    margin: 0 auto;
}

.search-header {
    text-align: center;
    margin-bottom: 2rem;
}

.search-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.search-header p {
    color: var(--text-secondary);
    font-size: 1.1rem;
}

.search-form-container {
    background: var(--surface-color);
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    margin-bottom: 2rem;
}

.search-form {
    margin-bottom: 0;
}

.search-input-group {
    display: flex;
    margin-bottom: 1.5rem;
}

.search-input {
    flex: 1;
    padding: 1rem 1.5rem;
    border: 2px solid var(--border-color);
    border-radius: var(--border-radius-sm) 0 0 var(--border-radius-sm);
    font-size: 1.1rem;
    outline: none;
    transition: var(--transition);
}

.search-input:focus {
    border-color: var(--primary-color);
}

.search-btn {
    padding: 1rem 1.5rem;
    background: var(--primary-color);
    color: white;
    border: none;
    border-radius: 0 var(--border-radius-sm) var(--border-radius-sm) 0;
    cursor: pointer;
    font-size: 1.1rem;
    transition: var(--transition);
}

.search-btn:hover {
    background: var(--primary-hover);
}

.search-filters {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.filter-option {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius-sm);
    transition: var(--transition);
}

.filter-option:hover {
    background: var(--border-color);
}

.filter-option input[type="radio"] {
    margin: 0;
}

.search-results {
    background: var(--surface-color);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    overflow: hidden;
}

.results-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    background: var(--background-color);
}

.results-header h2 {
    margin: 0;
    color: var(--text-primary);
}

.results-count {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.results-section {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.results-section:last-child {
    border-bottom: none;
}

.results-section h3 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    color: var(--text-primary);
}

.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1rem;
}

.user-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-sm);
    transition: var(--transition);
}

.user-card:hover {
    box-shadow: var(--shadow);
    transform: translateY(-1px);
}

.user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.user-info {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 600;
    color: var(--text-primary);
    text-decoration: none;
    display: block;
    margin-bottom: 0.25rem;
}

.user-name:hover {
    color: var(--primary-color);
}

.user-description {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin: 0 0 0.25rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.user-joined {
    color: var(--text-muted);
    font-size: 0.8rem;
}

.hashtags-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.hashtag-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-sm);
    transition: var(--transition);
}

.hashtag-card:hover {
    box-shadow: var(--shadow);
    transform: translateY(-1px);
}

.hashtag-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
    font-size: 1.1rem;
}

.hashtag-link:hover {
    text-decoration: underline;
}

.hashtag-count {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.search-suggestions {
    background: var(--surface-color);
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
    text-align: center;
}

.search-suggestions h3 {
    margin-bottom: 1rem;
    color: var(--text-primary);
}

.suggestion-tags {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: center;
}

.suggestion-tag {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: var(--primary-color);
    color: white;
    text-decoration: none;
    border-radius: var(--border-radius-sm);
    font-weight: 500;
    transition: var(--transition);
}

.suggestion-tag:hover {
    background: var(--primary-hover);
    transform: translateY(-1px);
}

@media (max-width: 768px) {
    .search-input-group {
        flex-direction: column;
    }
    
    .search-input {
        border-radius: var(--border-radius-sm);
        margin-bottom: 1rem;
    }
    
    .search-btn {
        border-radius: var(--border-radius-sm);
    }
    
    .search-filters {
        justify-content: center;
    }
    
    .users-grid {
        grid-template-columns: 1fr;
    }
    
    .hashtags-grid {
        grid-template-columns: 1fr;
    }
    
    .results-header {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
}
</style>
