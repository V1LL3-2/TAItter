<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/Hashtag.php';

$database = new Database();
$db = $database->getConnection();
$hashtag = new Hashtag($db);

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
    global $hashtag;
    
    $action = $_GET['action'] ?? 'all';
    $limit = min(intval($_GET['limit'] ?? 20), 100);
    
    switch($action) {
        case 'all':
            getAllHashtags($limit);
            break;
        case 'search':
            searchHashtags($limit);
            break;
        case 'followed':
            getFollowedHashtags();
            break;
        default:
            json_response(['error' => 'Invalid action'], 400);
    }
}

function handlePost() {
    global $hashtag;
    
    require_login();
    
    $data = json_decode(file_get_contents("php://input"));
    
    if(empty($data->hashtag_id)) {
        json_response(['error' => 'Hashtag ID is required'], 400);
        return;
    }
    
    if($hashtag->follow(get_current_user_id(), $data->hashtag_id)) {
        json_response(['message' => 'Hashtag followed successfully']);
    } else {
        json_response(['error' => 'Unable to follow hashtag'], 500);
    }
}

function handleDelete() {
    global $hashtag;
    
    require_login();
    
    $hashtag_id = $_GET['id'] ?? null;
    
    if(!$hashtag_id) {
        json_response(['error' => 'Hashtag ID is required'], 400);
        return;
    }
    
    if($hashtag->unfollow(get_current_user_id(), $hashtag_id)) {
        json_response(['message' => 'Hashtag unfollowed successfully']);
    } else {
        json_response(['error' => 'Unable to unfollow hashtag'], 500);
    }
}

function getAllHashtags($limit) {
    global $hashtag;
    
    $hashtags = $hashtag->getAll($limit);
    
    json_response([
        'hashtags' => $hashtags,
        'count' => count($hashtags)
    ]);
}

function searchHashtags($limit) {
    global $hashtag;
    
    $query = $_GET['q'] ?? null;
    
    if(!$query) {
        json_response(['error' => 'Search query is required'], 400);
        return;
    }
    
    $hashtags = $hashtag->search($query, $limit);
    
    json_response([
        'query' => $query,
        'hashtags' => $hashtags,
        'count' => count($hashtags)
    ]);
}

function getFollowedHashtags() {
    global $hashtag;
    
    if(!is_logged_in()) {
        json_response(['error' => 'Authentication required'], 401);
        return;
    }
    
    $hashtags = $hashtag->getFollowedByUser(get_current_user_id());
    
    json_response([
        'hashtags' => $hashtags,
        'count' => count($hashtags)
    ]);
}
?>
