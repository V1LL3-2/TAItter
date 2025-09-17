<?php
// Clean up any output buffering and suppress errors in JSON output
ob_start();
error_reporting(0); // Suppress PHP errors in JSON responses
ini_set('display_errors', 0);

// Get the correct path to the root directory
$root_path = dirname(__DIR__);
require_once $root_path . '/config/config.php';
require_once $root_path . '/config/database.php';
require_once $root_path . '/models/Post.php';
require_once $root_path . '/models/User.php';

// Clean the output buffer to ensure no HTML gets mixed with JSON
ob_clean();

// Set proper JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $post = new Post($db);
    $user = new User($db);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        handleGet();
        break;
    case 'POST':
        handlePost();
        break;
    case 'DELETE':
        handleDelete();
        break;
    default:
        echo json_encode(['error' => 'Method not allowed']);
        exit();
}

function handleGet() {
    global $post, $user;
    
    $action = $_GET['action'] ?? 'timeline';
    $limit = min(intval($_GET['limit'] ?? 20), 100);
    $offset = intval($_GET['offset'] ?? 0);
    
    try {
        switch($action) {
            case 'timeline':
                getTimeline($limit, $offset);
                break;
            case 'user':
                getUserPosts($limit, $offset);
                break;
            case 'hashtag':
                getHashtagPosts($limit, $offset);
                break;
            case 'search':
                searchPosts($limit, $offset);
                break;
            case 'all':
                getAllPosts($limit, $offset);
                break;
            default:
                echo json_encode(['error' => 'Invalid action']);
                exit();
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error']);
        exit();
    }
}

function handlePost() {
    global $post;
    
    try {
        // Check if user is logged in
        if(!is_logged_in()) {
            echo json_encode(['error' => 'Authentication required']);
            exit();
        }
        
        $input = file_get_contents("php://input");
        $data = json_decode($input);
        
        if(empty($data) || empty($data->content)) {
            echo json_encode(['error' => 'Content is required']);
            exit();
        }
        
        if(strlen($data->content) > 144) {
            echo json_encode(['error' => 'Post content must be 144 characters or less']);
            exit();
        }
        
        $post->user_id = get_current_user_id();
        $post->content = trim($data->content);
        
        if($post->create()) {
            // Parse and add hashtags
            $hashtags = $post->parseHashtags($data->content);
            if(!empty($hashtags)) {
                $post->addHashtags($post->id, $hashtags);
            }
            
            // Parse and add mentions
            $mentions = $post->parseMentions($data->content);
            if(!empty($mentions)) {
                $post->addMentions($post->id, $mentions);
            }
            
            // Get the created post with user info
            $created_post = $post->getById($post->id);
            
            echo json_encode([
                'message' => 'Post created successfully',
                'post' => $created_post
            ]);
        } else {
            echo json_encode(['error' => 'Unable to create post']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error']);
    }
    exit();
}

function handleDelete() {
    global $post;
    
    try {
        if(!is_logged_in()) {
            echo json_encode(['error' => 'Authentication required']);
            exit();
        }
        
        $post_id = $_GET['id'] ?? null;
        
        if(!$post_id) {
            echo json_encode(['error' => 'Post ID is required']);
            exit();
        }
        
        if($post->delete($post_id, get_current_user_id())) {
            echo json_encode(['message' => 'Post deleted successfully']);
        } else {
            echo json_encode(['error' => 'Unable to delete post']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error']);
    }
    exit();
}

function getTimeline($limit, $offset) {
    global $post;
    
    try {
        if(!is_logged_in()) {
            echo json_encode(['error' => 'Authentication required']);
            exit();
        }
        
        $posts = $post->getTimeline(get_current_user_id(), $limit, $offset);
        
        echo json_encode([
            'posts' => $posts,
            'count' => count($posts)
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load timeline']);
    }
    exit();
}

function getUserPosts($limit, $offset) {
    global $post, $user;
    
    try {
        $username = $_GET['username'] ?? null;
        
        if(!$username) {
            echo json_encode(['error' => 'Username is required']);
            exit();
        }
        
        if(!$user->getByUsername($username)) {
            echo json_encode(['error' => 'User not found']);
            exit();
        }
        
        $posts = $post->getByUser($user->id, $limit, $offset);
        
        echo json_encode([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'description' => $user->description,
                'created_at' => $user->created_at,
                'last_login' => $user->last_login
            ],
            'posts' => $posts,
            'count' => count($posts)
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load user posts']);
    }
    exit();
}

function getHashtagPosts($limit, $offset) {
    global $post;
    
    try {
        $hashtag = $_GET['hashtag'] ?? null;
        
        if(!$hashtag) {
            echo json_encode(['error' => 'Hashtag is required']);
            exit();
        }
        
        $posts = $post->getByHashtag($hashtag, $limit, $offset);
        
        echo json_encode([
            'hashtag' => $hashtag,
            'posts' => $posts,
            'count' => count($posts)
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load hashtag posts']);
    }
    exit();
}

function searchPosts($limit, $offset) {
    global $post;
    
    try {
        $query = $_GET['q'] ?? null;
        
        if(!$query) {
            echo json_encode(['error' => 'Search query is required']);
            exit();
        }
        
        $posts = $post->search($query, $limit, $offset);
        
        echo json_encode([
            'query' => $query,
            'posts' => $posts,
            'count' => count($posts)
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to search posts']);
    }
    exit();
}

function getAllPosts($limit, $offset) {
    global $post;
    
    try {
        $posts = $post->getAll($limit, $offset);
        
        echo json_encode([
            'posts' => $posts,
            'count' => count($posts)
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load posts']);
    }
    exit();
}
?>