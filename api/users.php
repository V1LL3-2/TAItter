<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/UserLike.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$userLike = new UserLike($db);

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
        json_response(['error' => 'Method not allowed'], 405);
}

function handleGet() {
    global $user, $userLike;
    
    $action = $_GET['action'] ?? 'profile';
    
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
            json_response(['error' => 'Invalid action'], 400);
    }
}

function handlePost() {
    global $userLike;
    
    require_login();
    
    $data = json_decode(file_get_contents("php://input"));
    
    if(empty($data->user_id)) {
        json_response(['error' => 'User ID is required'], 400);
        return;
    }
    
    if($data->user_id == get_current_user_id()) {
        json_response(['error' => 'Cannot like yourself'], 400);
        return;
    }
    
    if($userLike->like(get_current_user_id(), $data->user_id)) {
        json_response(['message' => 'User liked successfully']);
    } else {
        json_response(['error' => 'Unable to like user'], 500);
    }
}

function handleDelete() {
    global $userLike;
    
    require_login();
    
    $user_id = $_GET['id'] ?? null;
    
    if(!$user_id) {
        json_response(['error' => 'User ID is required'], 400);
        return;
    }
    
    if($userLike->unlike(get_current_user_id(), $user_id)) {
        json_response(['message' => 'User unliked successfully']);
    } else {
        json_response(['error' => 'Unable to unlike user'], 500);
    }
}

function handlePut() {
    global $user;
    
    require_login();
    
    $data = json_decode(file_get_contents("php://input"));
    
    if(empty($data->username) && empty($data->description)) {
        json_response(['error' => 'Username or description is required'], 400);
        return;
    }
    
    $user->id = get_current_user_id();
    
    if(isset($data->username)) {
        if(strlen($data->username) < 3 || strlen($data->username) > 50) {
            json_response(['error' => 'Username must be between 3 and 50 characters'], 400);
            return;
        }
        
        // Check if username is already taken by another user
        $existing_user = new User($GLOBALS['db']);
        if($existing_user->getByUsername($data->username) && $existing_user->id != $user->id) {
            json_response(['error' => 'Username already taken'], 409);
            return;
        }
        
        $user->username = $data->username;
    }
    
    if(isset($data->description)) {
        $user->description = $data->description;
    }
    
    if($user->update()) {
        json_response(['message' => 'Profile updated successfully']);
    } else {
        json_response(['error' => 'Unable to update profile'], 500);
    }
}

function getProfile() {
    global $user;
    
    $username = $_GET['username'] ?? null;
    
    if(!$username) {
        if(!is_logged_in()) {
            json_response(['error' => 'Authentication required'], 401);
            return;
        }
        $username = $_SESSION['username'];
    }
    
    if(!$user->getByUsername($username)) {
        json_response(['error' => 'User not found'], 404);
        return;
    }
    
    $stats = $user->getStats($user->id);
    
    json_response([
        'user' => [
            'id' => $user->id,
            'username' => $user->username,
            'description' => $user->description,
            'created_at' => $user->created_at,
            'last_login' => $user->last_login
        ],
        'stats' => $stats
    ]);
}

function searchUsers() {
    global $user;
    
    $query = $_GET['q'] ?? null;
    
    if(!$query) {
        json_response(['error' => 'Search query is required'], 400);
        return;
    }
    
    $users = $user->search($query);
    
    json_response([
        'query' => $query,
        'users' => $users,
        'count' => count($users)
    ]);
}

function getFollowers() {
    global $userLike;
    
    $user_id = $_GET['user_id'] ?? get_current_user_id();
    
    if(!$user_id) {
        json_response(['error' => 'User ID is required'], 400);
        return;
    }
    
    $followers = $userLike->getFollowers($user_id);
    
    json_response([
        'followers' => $followers,
        'count' => count($followers)
    ]);
}

function getFollowing() {
    global $userLike;
    
    $user_id = $_GET['user_id'] ?? get_current_user_id();
    
    if(!$user_id) {
        json_response(['error' => 'User ID is required'], 400);
        return;
    }
    
    $following = $userLike->getLikedByUser($user_id);
    
    json_response([
        'following' => $following,
        'count' => count($following)
    ]);
}

function getStats() {
    global $user;
    
    $user_id = $_GET['user_id'] ?? get_current_user_id();
    
    if(!$user_id) {
        json_response(['error' => 'User ID is required'], 400);
        return;
    }
    
    $stats = $user->getStats($user_id);
    
    json_response($stats);
}
?>
