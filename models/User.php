<?php
require_once 'config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $email;
    public $username;
    public $description;
    public $password_hash;
    public $created_at;
    public $updated_at;
    public $last_login;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new user
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET email=:email, username=:username, description=:description, password_hash=:password_hash";

        $stmt = $this->conn->prepare($query);

        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->description = htmlspecialchars(strip_tags($this->description));

        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":password_hash", $this->password_hash);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Check if email exists
    public function emailExists() {
        $query = "SELECT id, email, username, password_hash, description, created_at, last_login
                  FROM " . $this->table_name . " 
                  WHERE email = :email 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $this->email);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->email = $row['email'];
            $this->username = $row['username'];
            $this->password_hash = $row['password_hash'];
            $this->description = $row['description'];
            $this->created_at = $row['created_at'];
            $this->last_login = $row['last_login'];
            return true;
        }
        return false;
    }

    // Check if username exists
    public function usernameExists() {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $this->username);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Get user by ID
    public function getById($id) {
        $query = "SELECT id, email, username, description, created_at, last_login
                  FROM " . $this->table_name . " 
                  WHERE id = :id 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->email = $row['email'];
            $this->username = $row['username'];
            $this->description = $row['description'];
            $this->created_at = $row['created_at'];
            $this->last_login = $row['last_login'];
            return true;
        }
        return false;
    }

    // Get user by username
    public function getByUsername($username) {
        $query = "SELECT id, email, username, description, created_at, last_login
                  FROM " . $this->table_name . " 
                  WHERE username = :username 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->email = $row['email'];
            $this->username = $row['username'];
            $this->description = $row['description'];
            $this->created_at = $row['created_at'];
            $this->last_login = $row['last_login'];
            return true;
        }
        return false;
    }

    // Update last login
    public function updateLastLogin() {
        $query = "UPDATE " . $this->table_name . " 
                  SET last_login = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        return $stmt->execute();
    }

    // Update user profile
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET username=:username, description=:description 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->description = htmlspecialchars(strip_tags($this->description));

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    // Search users by username
    public function search($search_term, $limit = 10) {
        $query = "SELECT id, username, description, created_at, last_login
                  FROM " . $this->table_name . " 
                  WHERE username LIKE :search_term 
                  ORDER BY username ASC 
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $search_term = "%" . $search_term . "%";
        $stmt->bindParam(":search_term", $search_term);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get user stats
    public function getStats($user_id) {
        // Get followers count (users who like this user)
        $followers_query = "SELECT COUNT(*) as followers_count 
                           FROM user_likes_users 
                           WHERE liked_user_id = :user_id";
        $stmt = $this->conn->prepare($followers_query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $followers = $stmt->fetch(PDO::FETCH_ASSOC)['followers_count'];

        // Get following count (users this user likes)
        $following_query = "SELECT COUNT(*) as following_count 
                           FROM user_likes_users 
                           WHERE liker_id = :user_id";
        $stmt = $this->conn->prepare($following_query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $following = $stmt->fetch(PDO::FETCH_ASSOC)['following_count'];

        // Get posts count
        $posts_query = "SELECT COUNT(*) as posts_count 
                       FROM posts 
                       WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($posts_query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $posts = $stmt->fetch(PDO::FETCH_ASSOC)['posts_count'];

        return [
            'followers' => $followers,
            'following' => $following,
            'posts' => $posts
        ];
    }
}
?>
