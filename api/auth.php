<?php
// Clean up any output buffering and suppress errors in JSON output
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// Get the correct path to the root directory
$root_path = dirname(__DIR__);
require_once $root_path . '/config/config.php';
require_once $root_path . '/config/database.php';
require_once $root_path . '/models/User.php';

// Clean the output buffer
ob_clean();

// Set proper JSON headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
} catch (Exception $e) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Handle logout via GET as fallback
        logout();
        break;
    case 'POST':
        $input = file_get_contents("php://input");
        $data = json_decode($input);
        
        if(isset($data->action)) {
            switch($data->action) {
                case 'register':
                    register($user, $data);
                    break;
                case 'login':
                    login($user, $data);
                    break;
                case 'logout':
                    logout();
                    break;
                default:
                    echo json_encode(['error' => 'Invalid action']);
                    exit();
            }
        } else {
            // If no action specified, assume logout
            logout();
        }
        break;
    default:
        echo json_encode(['error' => 'Method not allowed']);
        exit();
}

function register($user, $data) {
    try {
        // Validate input
        if(empty($data->email) || empty($data->username) || empty($data->password)) {
            echo json_encode(['error' => 'Email, username, and password are required']);
            exit();
        }

        if(!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => 'Invalid email format']);
            exit();
        }

        if(strlen($data->password) < 6) {
            echo json_encode(['error' => 'Password must be at least 6 characters']);
            exit();
        }

        if(strlen($data->username) < 3 || strlen($data->username) > 50) {
            echo json_encode(['error' => 'Username must be between 3 and 50 characters']);
            exit();
        }

        // Check if email exists
        $user->email = $data->email;
        if($user->emailExists()) {
            echo json_encode(['error' => 'Email already exists']);
            exit();
        }

        // Check if username exists
        $user->username = $data->username;
        if($user->usernameExists()) {
            echo json_encode(['error' => 'Username already exists']);
            exit();
        }

        // Create user
        $user->email = $data->email;
        $user->username = $data->username;
        $user->description = $data->description ?? '';
        $user->password_hash = password_hash($data->password, PASSWORD_DEFAULT);

        if($user->create()) {
            echo json_encode([
                'message' => 'User created successfully',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->username,
                    'description' => $user->description
                ]
            ]);
        } else {
            echo json_encode(['error' => 'Unable to create user']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => 'Registration failed']);
    }
    exit();
}

function login($user, $data) {
    try {
        // Validate input
        if(empty($data->email) || empty($data->password)) {
            echo json_encode(['error' => 'Email and password are required']);
            exit();
        }

        // Check if user exists
        $user->email = $data->email;
        if(!$user->emailExists()) {
            echo json_encode(['error' => 'Invalid email or password']);
            exit();
        }

        // Verify password
        if(!password_verify($data->password, $user->password_hash)) {
            echo json_encode(['error' => 'Invalid email or password']);
            exit();
        }

        // Update last login
        $user->updateLastLogin();

        // Set session
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        $_SESSION['email'] = $user->email;

        echo json_encode([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'username' => $user->username,
                'description' => $user->description,
                'last_login' => $user->last_login
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Login failed']);
    }
    exit();
}

function logout() {
    try {
        session_destroy();
        
        // If this is a direct browser request (not AJAX), redirect to login
        if (!isset($_SERVER['HTTP_ACCEPT']) || strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false) {
            header('Location: ../login.php');
            exit();
        }
        
        echo json_encode(['message' => 'Logout successful']);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Logout failed']);
    }
    exit();
}
?>