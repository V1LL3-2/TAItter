<?php
class UserLike {
    private $conn;
    private $table_name = "user_likes_users";

    public $id;
    public $liker_id;
    public $liked_user_id;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Like a user
    public function like($liker_id, $liked_user_id) {
        $query = "INSERT IGNORE INTO " . $this->table_name . " (liker_id, liked_user_id) 
                  VALUES (:liker_id, :liked_user_id)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":liker_id", $liker_id);
        $stmt->bindParam(":liked_user_id", $liked_user_id);

        return $stmt->execute();
    }

    // Unlike a user
    public function unlike($liker_id, $liked_user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE liker_id = :liker_id AND liked_user_id = :liked_user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":liker_id", $liker_id);
        $stmt->bindParam(":liked_user_id", $liked_user_id);

        return $stmt->execute();
    }

    // Check if user likes another user
    public function isLiked($liker_id, $liked_user_id) {
        $query = "SELECT id FROM " . $this->table_name . " 
                  WHERE liker_id = :liker_id AND liked_user_id = :liked_user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":liker_id", $liker_id);
        $stmt->bindParam(":liked_user_id", $liked_user_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // Get users liked by a user
    public function getLikedByUser($user_id) {
        $query = "SELECT u.id, u.username, u.description, u.created_at, ulu.created_at as liked_at
                  FROM " . $this->table_name . " ulu
                  INNER JOIN users u ON ulu.liked_user_id = u.id
                  WHERE ulu.liker_id = :user_id
                  ORDER BY ulu.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get users who like a user (followers)
    public function getFollowers($user_id) {
        $query = "SELECT u.id, u.username, u.description, u.created_at, ulu.created_at as liked_at
                  FROM " . $this->table_name . " ulu
                  INNER JOIN users u ON ulu.liker_id = u.id
                  WHERE ulu.liked_user_id = :user_id
                  ORDER BY ulu.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get like count for a user
    public function getLikeCount($user_id) {
        $query = "SELECT COUNT(*) as like_count 
                  FROM " . $this->table_name . " 
                  WHERE liked_user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC)['like_count'];
    }

    // Get following count for a user
    public function getFollowingCount($user_id) {
        $query = "SELECT COUNT(*) as following_count 
                  FROM " . $this->table_name . " 
                  WHERE liker_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC)['following_count'];
    }
}
?>