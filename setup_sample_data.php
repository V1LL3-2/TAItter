<?php
// Sample data setup script
require_once 'config/config.php';
require_once 'config/database.php';

echo "<h1>TAItter Sample Data Setup</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "✅ Database connected<br>";
    
    // Clear existing data
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("TRUNCATE TABLE user_follows_hashtags");
    $db->exec("TRUNCATE TABLE user_likes_users");
    $db->exec("TRUNCATE TABLE post_mentions");
    $db->exec("TRUNCATE TABLE post_hashtags");
    $db->exec("TRUNCATE TABLE posts");
    $db->exec("TRUNCATE TABLE hashtags");
    $db->exec("TRUNCATE TABLE users");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "✅ Existing data cleared<br>";
    
    // Insert sample users
    $users = [
        ['admin', 'admin@taitter.com', 'Administrator account'],
        ['john_doe', 'john@example.com', 'Software developer and tech enthusiast'],
        ['jane_smith', 'jane@example.com', 'UI/UX Designer passionate about user experience'],
        ['bob_wilson', 'bob@example.com', 'Tech blogger and coffee lover'],
        ['alice_wonder', 'alice@example.com', 'AI researcher and data scientist']
    ];
    
    $password_hash = password_hash('password', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (username, email, password, description) VALUES (?, ?, ?, ?)");
    
    foreach($users as $user) {
        $stmt->execute([$user[0], $user[1], $password_hash, $user[2]]);
    }
    
    echo "✅ Sample users created (password for all: 'password')<br>";
    
    // Insert hashtags
    $hashtags = ['TAItter', 'innovation', 'tech', 'collaboration', 'teamwork', 'design', 'development', 'AI', 'coding', 'webdev'];
    
    $stmt = $db->prepare("INSERT INTO hashtags (tag) VALUES (?)");
    
    foreach($hashtags as $tag) {
        $stmt->execute([$tag]);
    }
    
    echo "✅ Sample hashtags created<br>";
    
    // Insert sample posts
    $posts = [
        [1, 'Welcome to #TAItter! The future of social media is here. #innovation #tech'],
        [2, 'Anyone else excited about the latest @jane_smith designs? #collaboration #design'],
        [3, 'Thanks @john_doe! Working together is always great. #teamwork'],
        [1, 'Building the next generation of social platforms. #development #tech'],
        [4, 'Coffee and #coding make the perfect combination! ☕ #webdev'],
        [5, 'Exploring the latest trends in #AI and machine learning. #innovation'],
        [2, 'Just finished a new project using the latest #tech stack! #development'],
        [3, 'User experience is everything in modern #design. #collaboration']
    ];
    
    $stmt = $db->prepare("INSERT INTO posts (user_id, content) VALUES (?, ?)");
    
    foreach($posts as $post) {
        $stmt->execute([$post[0], $post[1]]);
    }
    
    echo "✅ Sample posts created<br>";
    
    // Link hashtags to posts
    $post_hashtags = [
        [1, 1], [1, 2], [1, 3], // Post 1: TAItter, innovation, tech
        [2, 4], [2, 6], // Post 2: collaboration, design
        [3, 5], // Post 3: teamwork
        [4, 7], [4, 3], // Post 4: development, tech
        [5, 9], [5, 10], // Post 5: coding, webdev
        [6, 8], [6, 2], // Post 6: AI, innovation
        [7, 3], [7, 7], // Post 7: tech, development
        [8, 6], [8, 4] // Post 8: design, collaboration
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO post_hashtags (post_id, hashtag_id) VALUES (?, ?)");
    
    foreach($post_hashtags as $link) {
        $stmt->execute([$link[0], $link[1]]);
    }
    
    echo "✅ Post-hashtag links created<br>";
    
    // Add mentions
    $mentions = [
        [2, 3], // @jane_smith in post 2
        [3, 2] // @john_doe in post 3
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO post_mentions (post_id, mentioned_user_id) VALUES (?, ?)");
    
    foreach($mentions as $mention) {
        $stmt->execute([$mention[0], $mention[1]]);
    }
    
    echo "✅ Mentions created<br>";
    
    // Add some user likes
    $likes = [
        [1, 2], [1, 3], [1, 4], // admin likes john, jane, bob
        [2, 1], [2, 3], [2, 5], // john likes admin, jane, alice
        [3, 1], [3, 2], [3, 4], // jane likes admin, john, bob
        [4, 1], [4, 2], [4, 3], // bob likes admin, john, jane
        [5, 1], [5, 2] // alice likes admin, john
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO user_likes_users (liker_id, liked_user_id) VALUES (?, ?)");
    
    foreach($likes as $like) {
        $stmt->execute([$like[0], $like[1]]);
    }
    
    echo "✅ User likes created<br>";
    
    // Add hashtag follows
    $follows = [
        [1, 1], [1, 2], [1, 3], // admin follows TAItter, innovation, tech
        [2, 3], [2, 7], [2, 9], // john follows tech, development, coding
        [3, 6], [3, 4], [3, 2], // jane follows design, collaboration, innovation
        [4, 3], [4, 9], [4, 10], // bob follows tech, coding, webdev
        [5, 8], [5, 2], [5, 7] // alice follows AI, innovation, development
    ];
    
    $stmt = $db->prepare("INSERT IGNORE INTO user_follows_hashtags (user_id, hashtag_id) VALUES (?, ?)");
    
    foreach($follows as $follow) {
        $stmt->execute([$follow[0], $follow[1]]);
    }
    
    echo "✅ Hashtag follows created<br>";
    
    // Show final counts
    $counts = [
        'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'posts' => $db->query("SELECT COUNT(*) FROM posts")->fetchColumn(),
        'hashtags' => $db->query("SELECT COUNT(*) FROM hashtags")->fetchColumn(),
        'user_likes' => $db->query("SELECT COUNT(*) FROM user_likes_users")->fetchColumn(),
        'hashtag_follows' => $db->query("SELECT COUNT(*) FROM user_follows_hashtags")->fetchColumn()
    ];
    
    echo "<h2>Setup Complete!</h2>";
    echo "Created:<br>";
    foreach($counts as $table => $count) {
        echo "- $count $table<br>";
    }
    
    echo "<br><strong>You can now login with:</strong><br>";
    echo "Email: admin@taitter.com<br>";
    echo "Password: password<br>";
    echo "<br><a href='index.php'>← Go to TAItter</a><br>";
    echo "<a href='login.php'>← Go to Login</a>";
    
} catch(Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>