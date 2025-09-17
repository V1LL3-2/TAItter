<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Post.php';
require_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();
$post = new Post($db);
$user = new User($db);

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
        json_response(['error' => 'Method not allowed'], 405);
}

function handleGet() {
    global $post, $user;
    
    $action = $_GET['action'] ?? 'timeline';
    $limit = min(intval($_GET['limit'] ?? 20), 100);
    $offset = intval($_GET['offset'] ?? 0);
    
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
            json_response(['error' => 'Invalid action'], 400);
    }
}

function handlePost() {
    global $post;
    
    // Check if user is logged in
    if(!is_logged_in()) {
        json_response(['error' => 'Authentication required'], 401);
        return;
    }
    
    $data = json_decode(file_get_contents("php://input"));
    
    if(empty($data) || empty($data->content)) {
        json_response(['error' => 'Content is required'], 400);
        return;
    }
    
    if(strlen($data->content) > 144) {
        json_response(['error' => 'Post content must be 144 characters or less'], 400);
        return;
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
        
        json_response([
            'message' => 'Post created successfully',
            'post' => $created_post
        ], 201);
    } else {
        json_response(['error' => 'Unable to create post'], 500);
    }
}

function handleDelete() {
    global $post;
    
    require_login();
    
    $post_id = $_GET['id'] ?? null;
    
    if(!$post_id) {
        json_response(['error' => 'Post ID is required'], 400);
        return;
    }
    
    if($post->delete($post_id, get_current_user_id())) {
        json_response(['message' => 'Post deleted successfully']);
    } else {
        json_response(['error' => 'Unable to delete post or post not found'], 404);
    }
}

function getTimeline($limit, $offset) {
    global $post;
    
    if(!is_logged_in()) {
        json_response(['error' => 'Authentication required'], 401);
        return;
    }
    
    $posts = $post->getTimeline(get_current_user_id(), $limit, $offset);
    
    json_response([
        'posts' => $posts,
        'count' => count($posts)
    ]);
}

function getUserPosts($limit, $offset) {
    global $post;
    
    $username = $_GET['username'] ?? null;
    
    if(!$username) {
        json_response(['error' => 'Username is required'], 400);
        return;
    }
    
    $user = new User($GLOBALS['db']);
    if(!$user->getByUsername($username)) {
        json_response(['error' => 'User not found'], 404);
        return;
    }
    
    $posts = $post->getByUser($user->id, $limit, $offset);
    
    json_response([
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
}

function getHashtagPosts($limit, $offset) {
    global $post;
    
    $hashtag = $_GET['hashtag'] ?? null;
    
    if(!$hashtag) {
        json_response(['error' => 'Hashtag is required'], 400);
        return;
    }
    
    $posts = $post->getByHashtag($hashtag, $limit, $offset);
    
    json_response([
        'hashtag' => $hashtag,
        'posts' => $posts,
        'count' => count($posts)
    ]);
}

function searchPosts($limit, $offset) {
    global $post;
    
    $query = $_GET['q'] ?? null;
    
    if(!$query) {
        json_response(['error' => 'Search query is required'], 400);
        return;
    }
    
    $posts = $post->search($query, $limit, $offset);
    
    json_response([
        'query' => $query,
        'posts' => $posts,
        'count' => count($posts)
    ]);
}

function getAllPosts($limit, $offset) {
    global $post;
    
    $posts = $post->getAll($limit, $offset);
    
    json_response([
        'posts' => $posts,
        'count' => count($posts)
    ]);
}
?>
