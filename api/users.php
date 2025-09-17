<?php
// Clean up any output buffering and suppress errors in JSON output
ob_start();
error_reporting(0); // Suppress PHP errors in JSON responses
ini_set('display_errors', 0);

// Get the correct path to the root directory
$root_path = dirname(__DIR__);
require_once $root_path . '/config/config.php';
require_once $root_path . '/config/database.php';
require_once $root_path . '/models/User.php';
require_once $root_path . '/models/UserLike.php';

// Clean the output buffer to ensure no HTML gets mixed with JSON
ob_clean();

// Set proper JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    $userLike = new UserLike($db);
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
    case 'PUT':
        handlePut();
        break;
    default:
        echo json_encode(['error' => 'Method not allowed']);
        exit();
}

function handleGet() {
    global $user, $userLike;
    
    $action = $_GET['action'] ?? 'profile';
    
    try {
        switch($action) {
            case 'profile':
                getProfile();
                break;
            case 'search':
                searchUsers();
                break;
            case 'followers':
                getFollowers();
                break;
            case 'following':
                getFollowing();
                break;
            case 'stats':
                getStats();
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
    global $userLike;
    
    try {
        if(!is_logged_in()) {
            echo json_encode(['error' => 'Authentication required']);
            exit();
        }
        
        $input = file_get_contents("php://input");
        $data = json_decode($input);
        
        if(empty($data->user_id)) {
            echo json_encode(['error' => 'User ID is required']);
            exit();
        }
        
        $target_user_id = intval($data->user_id);
        $current_user_id = get_current_user_id();
        
        if($target_user_id == $current_user_id) {
            echo json_encode(['error' => 'Cannot like yourself']);
            exit();
        }
        
        if($userLike->like($current_user_id, $target_user_id)) {
            echo json_encode(['message' => 'User liked successfully']);
        } else {
            echo json_encode(['error' => 'Unable to like user']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error']);
    }
    exit();
}

function handleDelete() {
    global $userLike;
    
    try {
        if(!is_logged_in()) {
            echo json_encode(['error' => 'Authentication required']);
            exit();
        }
        
        $user_id = $_GET['id'] ?? null;
        
        if(!$user_id) {
            echo json_encode(['error' => 'User ID is required']);
            exit();
        }
        
        $target_user_id = intval($user_id);
        $current_user_id = get_current_user_id();
        
        if($userLike->unlike($current_user_id, $target_user_id)) {
            echo json_encode(['message' => 'User unliked successfully']);
        } else {
            echo json_encode(['error' => 'Unable to unlike user']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error']);
    }
    exit();
}

function handlePut() {
    global $user, $db;
    
    try {
        if(!is_logged_in()) {
            echo json_encode(['error' => 'Authentication required']);
            exit();
        }
        
        $input = file_get_contents("php://input");
        $data = json_decode($input);
        
        if(empty($data->username) && empty($data->description)) {
            echo json_encode(['error' => 'Username or description is required']);
            exit();
        }
        
        $user->id = get_current_user_id();
        
        if(isset($data->username)) {
            if(strlen($data->username) < 3 || strlen($data->username) > 50) {
                echo json_encode(['error' => 'Username must be between 3 and 50 characters']);
                exit();
            }
            
            // Check if username is already taken by another user
            $existing_user = new User($db);
            if($existing_user->getByUsername($data->username) && $existing_user->id != $user->id) {
                echo json_encode(['error' => 'Username already taken']);
                exit();
            }
            
            $user->username = $data->username;
        }
        
        if(isset($data->description)) {
            $user->description = $data->description;
        }
        
        if($user->update()) {
            echo json_encode(['message' => 'Profile updated successfully']);
        } else {
            echo json_encode(['error' => 'Unable to update profile']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error']);
    }
    exit();
}

function getProfile() {
    global $user;
    
    try {
        $username = $_GET['username'] ?? null;
        
        if(!$username) {
            if(!is_logged_in()) {
                echo json_encode(['error' => 'Authentication required']);
                exit();
            }
            $username = $_SESSION['username'];
        }
        
        if(!$user->getByUsername($username)) {
            echo json_encode(['error' => 'User not found']);
            exit();
        }
        
        $stats = $user->getStats($user->id);
        
        echo json_encode([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'description' => $user->description,
                'created_at' => $user->created_at,
                'last_login' => $user->last_login
            ],
            'stats' => $stats
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load profile']);
    }
    exit();
}

function searchUsers() {
    global $user;
    
    try {
        $query = $_GET['q'] ?? '';
        
        $users = $user->search($query);
        
        echo json_encode([
            'query' => $query,
            'users' => $users,
            'count' => count($users)
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to search users']);
    }
    exit();
}

function getFollowers() {
    global $userLike;
    
    try {
        $user_id = $_GET['user_id'] ?? get_current_user_id();
        
        if(!$user_id) {
            echo json_encode(['error' => 'User ID is required']);
            exit();
        }
        
        $followers = $userLike->getFollowers($user_id);
        
        echo json_encode([
            'followers' => $followers,
            'count' => count($followers)
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load followers']);
    }
    exit();
}

function getFollowing() {
    global $userLike;
    
    try {
        $user_id = $_GET['user_id'] ?? get_current_user_id();
        
        if(!$user_id) {
            echo json_encode(['error' => 'User ID is required']);
            exit();
        }
        
        $following = $userLike->getLikedByUser($user_id);
        
        echo json_encode([
            'following' => $following,
            'count' => count($following)
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load following']);
    }
    exit();
}

function getStats() {
    global $user;
    
    try {
        $user_id = $_GET['user_id'] ?? get_current_user_id();
        
        if(!$user_id) {
            echo json_encode(['error' => 'User ID is required']);
            exit();
        }
        
        $stats = $user->getStats($user_id);
        
        echo json_encode($stats);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load stats']);
    }
    exit();
}
?>