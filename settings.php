<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'models/Hashtag.php';
require_once 'models/UserLike.php';

// Require login
require_login();

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$hashtag = new Hashtag($db);
$userLike = new UserLike($db);

// Get current user
$user->getById(get_current_user_id());

$error = '';
$success = '';

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch($action) {
        case 'update_profile':
            $username = sanitize_input($_POST['username'] ?? '');
            $description = sanitize_input($_POST['description'] ?? '');
            
            if(empty($username)) {
                $error = 'Username is required';
            } elseif(strlen($username) < 3 || strlen($username) > 50) {
                $error = 'Username must be between 3 and 50 characters';
            } else {
                // Check if username is taken by another user
                $existing_user = new User($db);
                if($existing_user->getByUsername($username) && $existing_user->id != $user->id) {
                    $error = 'Username is already taken';
                } else {
                    $user->username = $username;
                    $user->description = $description;
                    
                    if($user->update()) {
                        $_SESSION['username'] = $username;
                        $success = 'Profile updated successfully';
                    } else {
                        $error = 'Failed to update profile';
                    }
                }
            }
            break;
            
        case 'follow_hashtag':
            $hashtag_id = intval($_POST['hashtag_id'] ?? 0);
            if($hashtag_id > 0) {
                if($hashtag->follow($user->id, $hashtag_id)) {
                    $success = 'Hashtag followed successfully';
                } else {
                    $error = 'Failed to follow hashtag';
                }
            }
            break;
            
        case 'unfollow_hashtag':
            $hashtag_id = intval($_POST['hashtag_id'] ?? 0);
            if($hashtag_id > 0) {
                if($hashtag->unfollow($user->id, $hashtag_id)) {
                    $success = 'Hashtag unfollowed successfully';
                } else {
                    $error = 'Failed to unfollow hashtag';
                }
            }
            break;
    }
}

// Get followed hashtags
$followed_hashtags = $hashtag->getFollowedByUser($user->id);

// Get liked users
$liked_users = $userLike->getLikedByUser($user->id);

// Get user stats
$stats = $user->getStats($user->id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - TAItter</title>
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
                        <a href="profile.php" class="nav-link">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                        <a href="settings.php" class="nav-link active">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                        <a href="api/auth.php" class="nav-link logout-btn" onclick="logout()">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main">
            <div class="container">
                <div class="settings-container">
                    <div class="settings-header">
                        <h1>Settings</h1>
                        <p>Manage your account and preferences</p>
                    </div>

                    <?php if($error): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="settings-grid">
                        <!-- Profile Settings -->
                        <div class="settings-section">
                            <div class="section-header">
                                <h2><i class="fas fa-user"></i> Profile Settings</h2>
                            </div>
                            
                            <form method="POST" class="settings-form">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="form-group">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" id="username" name="username" class="form-input" 
                                           value="<?php echo htmlspecialchars($user->username); ?>" 
                                           minlength="3" maxlength="50" required>
                                </div>

                                <div class="form-group">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" id="email" class="form-input" 
                                           value="<?php echo htmlspecialchars($user->email); ?>" disabled>
                                    <small class="form-help">Email cannot be changed</small>
                                </div>

                                <div class="form-group">
                                    <label for="description" class="form-label">Bio</label>
                                    <textarea id="description" name="description" class="form-input form-textarea" 
                                              placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user->description); ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i>
                                    Update Profile
                                </button>
                            </form>
                        </div>

                        <!-- Followed Hashtags -->
                        <div class="settings-section">
                            <div class="section-header">
                                <h2><i class="fas fa-hashtag"></i> Followed Hashtags</h2>
                                <span class="count"><?php echo count($followed_hashtags); ?></span>
                            </div>
                            
                            <div class="hashtags-list">
                                <?php if(empty($followed_hashtags)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-hashtag"></i>
                                        <p>You're not following any hashtags yet</p>
                                        <a href="search.php" class="btn btn-outline">Find Hashtags</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($followed_hashtags as $hashtag_item): ?>
                                        <div class="hashtag-item">
                                            <a href="hashtag.php?tag=<?php echo urlencode($hashtag_item['tag']); ?>" 
                                               class="hashtag-link">#<?php echo htmlspecialchars($hashtag_item['tag']); ?></a>
                                            <span class="followed-date">
                                                Following since <?php echo date('M j, Y', strtotime($hashtag_item['followed_at'])); ?>
                                            </span>
                                            <form method="POST" class="unfollow-form">
                                                <input type="hidden" name="action" value="unfollow_hashtag">
                                                <input type="hidden" name="hashtag_id" value="<?php echo $hashtag_item['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline" 
                                                        onclick="return confirm('Unfollow #<?php echo htmlspecialchars($hashtag_item['tag']); ?>?')">
                                                    <i class="fas fa-times"></i>
                                                    Unfollow
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Liked Users -->
                        <div class="settings-section">
                            <div class="section-header">
                                <h2><i class="fas fa-heart"></i> Liked Users</h2>
                                <span class="count"><?php echo count($liked_users); ?></span>
                            </div>
                            
                            <div class="users-list">
                                <?php if(empty($liked_users)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-heart"></i>
                                        <p>You haven't liked any users yet</p>
                                        <a href="search.php" class="btn btn-outline">Find Users</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($liked_users as $liked_user): ?>
                                        <div class="user-item">
                                            <div class="user-info">
                                                <div class="avatar">
                                                    <?php echo strtoupper(substr($liked_user['username'], 0, 1)); ?>
                                                </div>
                                                <div class="user-details">
                                                    <a href="profile.php?username=<?php echo urlencode($liked_user['username']); ?>" 
                                                       class="username">@<?php echo htmlspecialchars($liked_user['username']); ?></a>
                                                    <p class="user-description">
                                                        <?php echo htmlspecialchars($liked_user['description'] ?: 'No description'); ?>
                                                    </p>
                                                    <span class="liked-date">
                                                        Liked on <?php echo date('M j, Y', strtotime($liked_user['liked_at'])); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <button class="btn btn-sm btn-outline unlike-user-btn" 
                                                    data-user-id="<?php echo $liked_user['id']; ?>">
                                                <i class="fas fa-heart-broken"></i>
                                                Unlike
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Account Stats -->
                        <div class="settings-section">
                            <div class="section-header">
                                <h2><i class="fas fa-chart-bar"></i> Account Statistics</h2>
                            </div>
                            
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-comments"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-number"><?php echo $stats['posts']; ?></span>
                                        <span class="stat-label">Posts</span>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-number"><?php echo $stats['followers']; ?></span>
                                        <span class="stat-label">Followers</span>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-user-plus"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-number"><?php echo $stats['following']; ?></span>
                                        <span class="stat-label">Following</span>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <div class="stat-info">
                                        <span class="stat-number"><?php echo date('M Y', strtotime($user->created_at)); ?></span>
                                        <span class="stat-label">Joined</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        // Set current user ID for JavaScript
        window.currentUserId = <?php echo get_current_user_id(); ?>;
        
        // Handle unlike user buttons
        document.addEventListener('click', (e) => {
            if (e.target.closest('.unlike-user-btn')) {
                const button = e.target.closest('.unlike-user-btn');
                const userId = button.dataset.userId;
                
                if (confirm('Unlike this user?')) {
                    fetch('api/users.php', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ user_id: userId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.message) {
                            location.reload();
                        } else {
                            alert(data.error || 'Failed to unlike user');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Network error. Please try again.');
                    });
                }
            }
        });
    </script>
</body>
</html>

<style>
.settings-container {
    max-width: 1000px;
    margin: 0 auto;
}

.settings-header {
    text-align: center;
    margin-bottom: 2rem;
}

.settings-header h1 {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.settings-header p {
    color: var(--text-secondary);
    font-size: 1.1rem;
}

.settings-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
}

.settings-section {
    background: var(--surface-color);
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow);
    border: 1px solid var(--border-color);
}

.section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}

.section-header h2 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    color: var(--text-primary);
}

.count {
    background: var(--primary-color);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.settings-form {
    margin-bottom: 0;
}

.hashtags-list, .users-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.hashtag-item, .user-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-sm);
    transition: var(--transition);
}

.hashtag-item:hover, .user-item:hover {
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

.followed-date, .liked-date {
    color: var(--text-muted);
    font-size: 0.85rem;
    margin-top: 0.25rem;
}

.unfollow-form {
    margin: 0;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
}

.user-info .avatar {
    width: 40px;
    height: 40px;
    font-size: 1rem;
}

.user-details {
    flex: 1;
    min-width: 0;
}

.username {
    font-weight: 600;
    color: var(--text-primary);
    text-decoration: none;
    display: block;
    margin-bottom: 0.25rem;
}

.username:hover {
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

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius-sm);
    transition: var(--transition);
}

.stat-card:hover {
    box-shadow: var(--shadow);
    transform: translateY(-1px);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    line-height: 1;
}

.stat-label {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin-top: 0.25rem;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 1rem;
    color: var(--border-color);
}

.empty-state p {
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .hashtag-item, .user-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .user-info {
        width: 100%;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>
