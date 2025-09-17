<?php
require_once 'config/database.php';

class Post {
    private $conn;
    private $table_name = "posts";

    public $id;
    public $user_id;
    public $content;
    public $created_at;
    public $updated_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new post
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET user_id=:user_id, content=:content";

        $stmt = $this->conn->prepare($query);

        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->content = htmlspecialchars(strip_tags($this->content));

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":content", $this->content);

        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Get post by ID with user info
    public function getById($id) {
        $query = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                         u.username, u.description as user_description
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.user_id = u.id
                  WHERE p.id = :id 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return false;
    }

    // Get posts for timeline (with hashtags and mentions)
    public function getTimeline($user_id, $limit = 20, $offset = 0) {
        $query = "SELECT DISTINCT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                         u.username, u.description as user_description
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.user_id = u.id
                  LEFT JOIN post_hashtags ph ON p.id = ph.post_id
                  LEFT JOIN hashtags h ON ph.hashtag_id = h.id
                  LEFT JOIN user_follows_hashtags ufh ON h.id = ufh.hashtag_id
                  LEFT JOIN post_mentions pm ON p.id = pm.post_id
                  LEFT JOIN user_likes_users ulu ON pm.mentioned_user_id = ulu.liked_user_id
                  WHERE ufh.user_id = :user_id1 
                     OR ulu.liker_id = :user_id2
                     OR pm.mentioned_user_id = :user_id3
                     OR p.user_id = :user_id4
                  ORDER BY p.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id1", $user_id);
        $stmt->bindParam(":user_id2", $user_id);
        $stmt->bindParam(":user_id3", $user_id);
        $stmt->bindParam(":user_id4", $user_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get posts by user
    public function getByUser($user_id, $limit = 20, $offset = 0) {
        $query = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                         u.username, u.description as user_description
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.user_id = u.id
                  WHERE p.user_id = :user_id
                  ORDER BY p.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get posts by hashtag
    public function getByHashtag($hashtag, $limit = 20, $offset = 0) {
        $query = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                         u.username, u.description as user_description
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.user_id = u.id
                  LEFT JOIN post_hashtags ph ON p.id = ph.post_id
                  LEFT JOIN hashtags h ON ph.hashtag_id = h.id
                  WHERE h.tag = :hashtag
                  ORDER BY p.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":hashtag", $hashtag);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get all posts (for public timeline)
    public function getAll($limit = 20, $offset = 0) {
        $query = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                         u.username, u.description as user_description
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.user_id = u.id
                  ORDER BY p.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Parse hashtags from content
    public function parseHashtags($content) {
        preg_match_all('/#(\w+)/', $content, $matches);
        return $matches[1];
    }

    // Parse mentions from content
    public function parseMentions($content) {
        preg_match_all('/@(\w+)/', $content, $matches);
        return $matches[1];
    }

    // Add hashtags to post
    public function addHashtags($post_id, $hashtags) {
        foreach($hashtags as $tag) {
            // Check if hashtag exists
            $hashtag_query = "SELECT id FROM hashtags WHERE tag = :tag";
            $stmt = $this->conn->prepare($hashtag_query);
            $stmt->bindParam(":tag", $tag);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $hashtag_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
            } else {
                // Create new hashtag
                $insert_hashtag = "INSERT INTO hashtags (tag) VALUES (:tag)";
                $stmt = $this->conn->prepare($insert_hashtag);
                $stmt->bindParam(":tag", $tag);
                $stmt->execute();
                $hashtag_id = $this->conn->lastInsertId();
            }

            // Link post to hashtag
            $link_query = "INSERT IGNORE INTO post_hashtags (post_id, hashtag_id) VALUES (:post_id, :hashtag_id)";
            $stmt = $this->conn->prepare($link_query);
            $stmt->bindParam(":post_id", $post_id);
            $stmt->bindParam(":hashtag_id", $hashtag_id);
            $stmt->execute();
        }
    }

    // Add mentions to post
    public function addMentions($post_id, $mentions) {
        foreach($mentions as $username) {
            // Get user ID
            $user_query = "SELECT id FROM users WHERE username = :username";
            $stmt = $this->conn->prepare($user_query);
            $stmt->bindParam(":username", $username);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $user_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
                
                // Link post to mention
                $link_query = "INSERT IGNORE INTO post_mentions (post_id, mentioned_user_id) VALUES (:post_id, :mentioned_user_id)";
                $stmt = $this->conn->prepare($link_query);
                $stmt->bindParam(":post_id", $post_id);
                $stmt->bindParam(":mentioned_user_id", $user_id);
                $stmt->execute();
            }
        }
    }

    // Search posts by content
    public function search($search_term, $limit = 20, $offset = 0) {
        $query = "SELECT p.id, p.user_id, p.content, p.created_at, p.updated_at,
                         u.username, u.description as user_description
                  FROM " . $this->table_name . " p
                  LEFT JOIN users u ON p.user_id = u.id
                  WHERE p.content LIKE :search_term
                  ORDER BY p.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $search_term = "%" . $search_term . "%";
        $stmt->bindParam(":search_term", $search_term);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Delete post
    public function delete($post_id, $user_id) {
        $query = "DELETE FROM " . $this->table_name . " 
                  WHERE id = :post_id AND user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":post_id", $post_id);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }
}
?>
