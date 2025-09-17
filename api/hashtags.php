<?php
// Clean up any output buffering and suppress errors in JSON output
ob_start();
error_reporting(0); // Suppress PHP errors in JSON responses
ini_set('display_errors', 0);

// Get the correct path to the root directory
$root_path = dirname(__DIR__);
require_once $root_path . '/config/config.php';
require_once $root_path . '/config/database.php';
require_once $root_path . '/models/Hashtag.php';

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
    $hashtag = new Hashtag($db);
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
    global $hashtag;
    
    $action = $_GET['action'] ?? 'all';
    $limit = min(intval($_GET['limit'] ?? 20), 100);
    
    try {
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
                echo json_encode(['error' => 'Invalid action']);
                exit();
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error']);
        exit();
    }
}

function handlePost() {
    global $hashtag;
    
    try {
        if(!is_logged_in()) {
            echo json_encode(['error' => 'Authentication required']);
            exit();
        }
        
        $input = file_get_contents("php://input");
        $data = json_decode($input);
        
        if(empty($data->hashtag_id)) {
            echo json_encode(['error' => 'Hashtag ID is required']);
            exit();
        }
        
        if($hashtag->follow(get_current_user_id(), $data->hashtag_id)) {
            echo json_encode(['message' => 'Hashtag followed successfully']);
        } else {
            echo json_encode(['error' => 'Unable to follow hashtag']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error']);
    }
    exit();
}

function handleDelete() {
    global $hashtag;
    
    try {
        if(!is_logged_in()) {
            echo json_encode(['error' => 'Authentication required']);
            exit();
        }
        
        $hashtag_id = $_GET['id'] ?? null;
        
        if(!$hashtag_id) {
            echo json_encode(['error' => 'Hashtag ID is required']);
            exit();
        }
        
        if($hashtag->unfollow(get_current_user_id(), $hashtag_id)) {
            echo json_encode(['message' => 'Hashtag unfollowed successfully']);
        } else {
            echo json_encode(['error' => 'Unable to unfollow hashtag']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Server error']);
    }
    exit();
}

function getAllHashtags($limit) {
    global $hashtag;
    
    try {
        $hashtags = $hashtag->getAll($limit);
        
        echo json_encode([
            'hashtags' => $hashtags,
            'count' => count($hashtags)
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load hashtags']);
    }
    exit();
}

function searchHashtags($limit) {
    global $hashtag;
    
    try {
        $query = $_GET['q'] ?? null;
        
        if(!$query) {
            echo json_encode(['error' => 'Search query is required']);
            exit();
        }
        
        $hashtags = $hashtag->search($query, $limit);
        
        echo json_encode([
            'query' => $query,
            'hashtags' => $hashtags,
            'count' => count($hashtags)
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to search hashtags']);
    }
    exit();
}

function getFollowedHashtags() {
    global $hashtag;
    
    try {
        if(!is_logged_in()) {
            echo json_encode(['error' => 'Authentication required']);
            exit();
        }
        
        $hashtags = $hashtag->getFollowedByUser(get_current_user_id());
        
        echo json_encode([
            'hashtags' => $hashtags,
            'count' => count($hashtags)
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Failed to load followed hashtags']);
    }
    exit();
}
?>