<?php
class Hashtag {
    private $conn;
    private $table_name = "hashtags";

    public $id;
    public $tag;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Get hashtag by tag name
    public function getByTag($tag) {
        $query = "SELECT id, tag, created_at
                  FROM " . $this->table_name . " 
                  WHERE tag = :tag 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":tag", $tag);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->tag = $row['tag'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    // Get all hashtags
    public function getAll($limit = 50) {
        $query = "SELECT h.id, h.tag, h.created_at, COUNT(ph.post_id) as post_count
                  FROM " . $this->table_name . " h
                  LEFT JOIN post_hashtags ph ON h.id = ph.hashtag_id
                  GROUP BY h.id, h.tag, h.created_at
                  ORDER BY post_count DESC, h.tag ASC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Search hashtags
    public function search($search_term, $limit = 10) {
        $query = "SELECT h.id, h.tag, h.created_at, COUNT(ph.post_id) as post_count
                  FROM " . $this->table_name . " h
                  LEFT JOIN post_hashtags ph ON h.id = ph.hashtag_id
                  WHERE h.tag LIKE :search_term
                  GROUP BY h.id, h.tag, h.created_at
                  ORDER BY post_count DESC, h.tag ASC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $search_term = "%" . $search_term . "%";
        $stmt->bindParam(":search_term", $search_term);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get hashtags followed by user
    public function getFollowedByUser($user_id) {
        $query = "SELECT h.id, h.tag, h.created_at, ufh.created_at as followed_at
                  FROM " . $this->table_name . " h
                  INNER JOIN user_follows_hashtags ufh ON h.id = ufh.hashtag_id
                  WHERE ufh.user_id = :user_id
                  ORDER BY ufh.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Follow hashtag
    public function follow($user_id, $hashtag_id) {
        $query = "INSERT IGNORE INTO user_follows_hashtags (user_id, hashtag_id) 
                  VALUES (:user_id, :hashtag_id)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":hashtag_id", $hashtag_id);

        return $stmt->execute();
    }

    // Unfollow hashtag
    public function unfollow($user_id, $hashtag_id) {
        $query = "DELETE FROM user_follows_hashtags 
                  WHERE user_id = :user_id AND hashtag_id = :hashtag_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":hashtag_id", $hashtag_id);

        return $stmt->execute();
    }

    // Check if user follows hashtag
    public function isFollowedByUser($user_id, $hashtag_id) {
        $query = "SELECT id FROM user_follows_hashtags 
                  WHERE user_id = :user_id AND hashtag_id = :hashtag_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":hashtag_id", $hashtag_id);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
}
?>