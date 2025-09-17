<?php
require_once 'config/config.php';

// Redirect if already logged in
if(is_logged_in()) {
    redirect('index.php');
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $description = sanitize_input($_POST['description'] ?? '');
    
    // Validation
    if(empty($email) || empty($username) || empty($password)) {
        $error = 'Email, username, and password are required';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif(strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif(strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters';
    } else {
        require_once 'config/database.php';
        require_once 'models/User.php';
        
        try {
            $database = new Database();
            $db = $database->getConnection();
            $user = new User($db);
            
            // Check if email exists
            $user->email = $email;
            if($user->emailExists()) {
                $error = 'Email already exists';
            } else {
                // Check if username exists
                $user->username = $username;
                if($user->usernameExists()) {
                    $error = 'Username already exists';
                } else {
                    // Create user
                    $user->email = $email;
                    $user->username = $username;
                    $user->description = $description;
                    $user->password_hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    if($user->create()) {
                        $success = 'Account created successfully! You can now login.';
                        // Clear form
                        $email = $username = $description = '';
                    } else {
                        $error = 'Unable to create account. Please try again.';
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'Registration failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TAItter</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-bolt"></i>
                    <span>TAItter</span>
                </div>
                <h2>Join TAItter today!</h2>
                <p>Create your account and start sharing</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" id="username" name="username" class="form-input" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                           minlength="3" maxlength="50" required>
                </div>

                <div class="form-group">
                    <label for="description" class="form-label">Bio (Optional)</label>
                    <textarea id="description" name="description" class="form-input form-textarea" 
                              placeholder="Tell us about yourself..."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="form-input" 
                           minlength="6" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                           minlength="6" required>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
                <p><a href="index.php">← Back to Home</a></p>
            </div>
        </div>
    </div>

    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color) 0%, #0d8bd9 100%);
            padding: 2rem;
        }

        .auth-card {
            background: var(--surface-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-hover);
            width: 100%;
            max-width: 450px;
            padding: 2rem;
        }

        .auth-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .auth-header .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .auth-header h2 {
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .auth-header p {
            color: var(--text-secondary);
        }

        .auth-form {
            margin-bottom: 2rem;
        }

        .auth-footer {
            text-align: center;
            color: var(--text-secondary);
        }

        .auth-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        .auth-footer p {
            margin-bottom: 0.5rem;
        }

        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }
    </style>
</body>
</html>