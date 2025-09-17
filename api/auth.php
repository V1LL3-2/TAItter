<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
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
                    json_response(['error' => 'Invalid action'], 400);
            }
        } else {
            json_response(['error' => 'Action required'], 400);
        }
        break;
    default:
        json_response(['error' => 'Method not allowed'], 405);
}

function register($user, $data) {
    // Validate input
    if(empty($data->email) || empty($data->username) || empty($data->password)) {
        json_response(['error' => 'Email, username, and password are required'], 400);
        return;
    }

    if(!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        json_response(['error' => 'Invalid email format'], 400);
        return;
    }

    if(strlen($data->password) < 6) {
        json_response(['error' => 'Password must be at least 6 characters'], 400);
        return;
    }

    if(strlen($data->username) < 3 || strlen($data->username) > 50) {
        json_response(['error' => 'Username must be between 3 and 50 characters'], 400);
        return;
    }

    // Check if email exists
    $user->email = $data->email;
    if($user->emailExists()) {
        json_response(['error' => 'Email already exists'], 409);
        return;
    }

    // Check if username exists
    $user->username = $data->username;
    if($user->usernameExists()) {
        json_response(['error' => 'Username already exists'], 409);
        return;
    }

    // Create user
    $user->email = $data->email;
    $user->username = $data->username;
    $user->description = $data->description ?? '';
    $user->password_hash = password_hash($data->password, PASSWORD_DEFAULT);

    if($user->create()) {
        json_response([
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'username' => $user->username,
                'description' => $user->description
            ]
        ], 201);
    } else {
        json_response(['error' => 'Unable to create user'], 500);
    }
}

function login($user, $data) {
    // Validate input
    if(empty($data->email) || empty($data->password)) {
        json_response(['error' => 'Email and password are required'], 400);
        return;
    }

    // Check if user exists
    $user->email = $data->email;
    if(!$user->emailExists()) {
        json_response(['error' => 'Invalid email or password'], 401);
        return;
    }

    // Verify password
    if(!password_verify($data->password, $user->password_hash)) {
        json_response(['error' => 'Invalid email or password'], 401);
        return;
    }

    // Update last login
    $user->updateLastLogin();

    // Set session
    $_SESSION['user_id'] = $user->id;
    $_SESSION['username'] = $user->username;
    $_SESSION['email'] = $user->email;

    json_response([
        'message' => 'Login successful',
        'user' => [
            'id' => $user->id,
            'email' => $user->email,
            'username' => $user->username,
            'description' => $user->description,
            'last_login' => $user->last_login
        ]
    ]);
}

function logout() {
    session_destroy();
    json_response(['message' => 'Logout successful']);
}
?>
