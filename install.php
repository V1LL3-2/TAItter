<?php
/**
 * TAItter Installation Script
 * Run this script to set up the database and verify installation
 */

// Check if already installed
if (file_exists('config/installed.flag')) {
    die('TAItter is already installed. Delete config/installed.flag to reinstall.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'taitter';
    $db_user = $_POST['db_user'] ?? 'root';
    $db_pass = $_POST['db_pass'] ?? '';
    
    try {
        // Test database connection
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `$db_name`");
        
        // Read and execute schema
        $schema = file_get_contents('database/schema.sql');
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        // Update database config
        $config_content = "<?php
class Database {
    private \$host = '$db_host';
    private \$db_name = '$db_name';
    private \$username = '$db_user';
    private \$password = '$db_pass';
    private \$conn;

    public function getConnection() {
        \$this->conn = null;
        
        try {
            \$this->conn = new PDO(
                \"mysql:host=\" . \$this->host . \";dbname=\" . \$this->db_name . \";charset=utf8mb4\",
                \$this->username,
                \$this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException \$exception) {
            echo \"Connection error: \" . \$exception->getMessage();
        }
        
        return \$this->conn;
    }
}
?>";
        
        file_put_contents('config/database.php', $config_content);
        
        // Create installed flag
        file_put_contents('config/installed.flag', date('Y-m-d H:i:s'));
        
        $success = 'TAItter has been successfully installed! You can now <a href="index.php">access the application</a>.';
        
    } catch (Exception $e) {
        $error = 'Installation failed: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TAItter Installation</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1da1f2 0%, #0d8bd9 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .install-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            padding: 2rem;
        }
        
        .install-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 2rem;
            font-weight: 700;
            color: #1da1f2;
            margin-bottom: 1rem;
        }
        
        .install-header h1 {
            margin-bottom: 0.5rem;
            color: #14171a;
        }
        
        .install-header p {
            color: #657786;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #14171a;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e1e8ed;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s ease;
            outline: none;
        }
        
        .form-input:focus {
            border-color: #1da1f2;
            box-shadow: 0 0 0 3px rgba(29, 161, 242, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: #1da1f2;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            font-size: 1rem;
        }
        
        .btn:hover {
            background: #0d8bd9;
            transform: translateY(-1px);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .requirements {
            background: #f7f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .requirements h3 {
            margin-bottom: 0.5rem;
            color: #14171a;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }
        
        .requirement i {
            color: #17bf63;
        }
        
        .requirement.failed i {
            color: #e0245e;
        }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="install-header">
            <div class="logo">
                <i class="fas fa-bolt"></i>
                <span>TAItter</span>
            </div>
            <h1>Installation</h1>
            <p>Set up your TAItter installation</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php else: ?>
            <!-- System Requirements Check -->
            <div class="requirements">
                <h3>System Requirements</h3>
                <div class="requirement <?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? '' : 'failed'; ?>">
                    <i class="fas fa-<?php echo version_compare(PHP_VERSION, '7.4.0', '>=') ? 'check' : 'times'; ?>"></i>
                    <span>PHP <?php echo PHP_VERSION; ?> (Required: 7.4+)</span>
                </div>
                <div class="requirement <?php echo extension_loaded('pdo') ? '' : 'failed'; ?>">
                    <i class="fas fa-<?php echo extension_loaded('pdo') ? 'check' : 'times'; ?>"></i>
                    <span>PDO Extension</span>
                </div>
                <div class="requirement <?php echo extension_loaded('pdo_mysql') ? '' : 'failed'; ?>">
                    <i class="fas fa-<?php echo extension_loaded('pdo_mysql') ? 'check' : 'times'; ?>"></i>
                    <span>PDO MySQL Extension</span>
                </div>
                <div class="requirement <?php echo is_writable('config/') ? '' : 'failed'; ?>">
                    <i class="fas fa-<?php echo is_writable('config/') ? 'check' : 'times'; ?>"></i>
                    <span>Config Directory Writable</span>
                </div>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label for="db_host" class="form-label">Database Host</label>
                    <input type="text" id="db_host" name="db_host" class="form-input" 
                           value="localhost" required>
                </div>

                <div class="form-group">
                    <label for="db_name" class="form-label">Database Name</label>
                    <input type="text" id="db_name" name="db_name" class="form-input" 
                           value="taitter" required>
                </div>

                <div class="form-group">
                    <label for="db_user" class="form-label">Database Username</label>
                    <input type="text" id="db_user" name="db_user" class="form-input" 
                           value="root" required>
                </div>

                <div class="form-group">
                    <label for="db_pass" class="form-label">Database Password</label>
                    <input type="password" id="db_pass" name="db_pass" class="form-input">
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-download"></i>
                    Install TAItter
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
