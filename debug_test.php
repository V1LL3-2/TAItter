<?php
// Debug test file - Place this in the root directory
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>TAItter Debug Test</h1>";

// Test 1: Check if config files exist
echo "<h2>1. File Check</h2>";
$files = [
    'config/config.php',
    'config/database.php',
    'models/Post.php',
    'models/User.php',
    'api/posts.php'
];

foreach($files as $file) {
    if(file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file missing<br>";
    }
}

// Test 2: Database connection
echo "<h2>2. Database Connection Test</h2>";
try {
    require_once 'config/config.php';
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    echo "✅ Database connection successful<br>";
    
    // Test if tables exist
    $tables = ['users', 'posts', 'hashtags', 'user_likes_users', 'post_hashtags', 'post_mentions', 'user_follows_hashtags'];
    
    foreach($tables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if($stmt->rowCount() > 0) {
            echo "✅ Table $table exists<br>";
        } else {
            echo "❌ Table $table missing<br>";
        }
    }
    
} catch(Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 3: Session check
echo "<h2>3. Session Test</h2>";
if(session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session active<br>";
    echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";
    echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "<br>";
} else {
    echo "❌ Session not active<br>";
}

// Test 4: API endpoint test
echo "<h2>4. API Test</h2>";
try {
    // Test posts API directly
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET['action'] = 'all';
    $_GET['limit'] = 5;
    
    ob_start();
    include 'api/posts.php';
    $output = ob_get_clean();
    
    if(empty($output)) {
        echo "❌ Posts API returned empty response<br>";
    } else {
        echo "✅ Posts API working<br>";
        echo "Response preview: " . substr($output, 0, 200) . "...<br>";
    }
    
} catch(Exception $e) {
    echo "❌ API error: " . $e->getMessage() . "<br>";
}

// Test 5: Check sample data
echo "<h2>5. Sample Data Check</h2>";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'];
    echo "Users in database: $userCount<br>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM posts");
    $postCount = $stmt->fetch()['count'];
    echo "Posts in database: $postCount<br>";
    
    if($userCount == 0) {
        echo "❌ No users found - database might not be properly initialized<br>";
    }
    
} catch(Exception $e) {
    echo "❌ Sample data error: " . $e->getMessage() . "<br>";
}

echo "<br><strong>Test completed. Check the results above to identify issues.</strong>";
?>