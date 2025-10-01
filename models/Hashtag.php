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
                  WHERE LOWER(tag) = LOWER(:tag) 
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

    // Get hashtag by ID
    public function getById($id) {
        $query = "SELECT id, tag, created_at
                  FROM " . $this->table_name . " 
                  WHERE id = :id 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
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

    // Get all hashtags with post count
    public function getAll($limit = 50) {
        $query = "SELECT h.id, h.tag, h.created_at, 
                         COUNT(ph.post_id) as post_count,
                         COUNT(DISTINCT ufh.user_id) as follower_count,
                         MAX(p.created_at) as last_post_date
                  FROM " . $this->table_name . " h
                  LEFT JOIN post_hashtags ph ON h.id = ph.hashtag_id
                  LEFT JOIN user_follows_hashtags ufh ON h.id = ufh.hashtag_id
                  LEFT JOIN posts p ON ph.post_id = p.id
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
        $query = "SELECT h.id, h.tag, h.created_at, 
                         COUNT(ph.post_id) as post_count,
                         COUNT(DISTINCT ufh.user_id) as follower_count
                  FROM " . $this->table_name . " h
                  LEFT JOIN post_hashtags ph ON h.id = ph.hashtag_id
                  LEFT JOIN user_follows_hashtags ufh ON h.id = ufh.hashtag_id
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

    // Get trending hashtags (most used in recent time)
    public function getTrending($hours = 24, $limit = 10) {
        $query = "SELECT h.id, h.tag, h.created_at,
                         COUNT(ph.post_id) as recent_post_count,
                         COUNT(DISTINCT p.user_id) as unique_users
                  FROM " . $this->table_name . " h
                  INNER JOIN post_hashtags ph ON h.id = ph.hashtag_id
                  INNER JOIN posts p ON ph.post_id = p.id
                  WHERE p.created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
                  GROUP BY h.id, h.tag, h.created_at
                  ORDER BY recent_post_count DESC, unique_users DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":hours", $hours, PDO::PARAM_INT);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get hashtags followed by user
    public function getFollowedByUser($user_id) {
        $query = "SELECT h.id, h.tag, h.created_at, ufh.created_at as followed_at,
                         COUNT(ph.post_id) as post_count
                  FROM " . $this->table_name . " h
                  INNER JOIN user_follows_hashtags ufh ON h.id = ufh.hashtag_id
                  LEFT JOIN post_hashtags ph ON h.id = ph.hashtag_id
                  WHERE ufh.user_id = :user_id
                  GROUP BY h.id, h.tag, h.created_at, ufh.created_at
                  ORDER BY ufh.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get related hashtags (hashtags that appear together in posts)
    public function getRelatedHashtags($hashtag_id, $limit = 5) {
        $query = "
        SELECT h2.id, h2.tag, COUNT(ph2.post_id) as co_occurrence_count,
               COUNT(DISTINCT p.user_id) as unique_users
        FROM post_hashtags ph1
        JOIN post_hashtags ph2 ON ph1.post_id = ph2.post_id AND ph2.hashtag_id != ph1.hashtag_id
        JOIN hashtags h2 ON ph2.hashtag_id = h2.id
        JOIN posts p ON ph1.post_id = p.id
        WHERE ph1.hashtag_id = :hashtag_id
        GROUP BY h2.id, h2.tag
        ORDER BY co_occurrence_count DESC, unique_users DESC, h2.tag ASC
        LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":hashtag_id", $hashtag_id, PDO::PARAM_INT);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get hashtag statistics
    public function getHashtagStats($hashtag_id) {
        $query = "
        SELECT 
            h.tag,
            h.created_at,
            COUNT(DISTINCT ph.post_id) as total_posts,
            COUNT(DISTINCT p.user_id) as unique_users,
            COUNT(DISTINCT ufh.user_id) as followers_count,
            MIN(p.created_at) as first_post_date,
            MAX(p.created_at) as last_post_date,
            COUNT(CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as posts_last_24h,
            COUNT(CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as posts_last_week
        FROM hashtags h
        LEFT JOIN post_hashtags ph ON h.id = ph.hashtag_id
        LEFT JOIN posts p ON ph.post_id = p.id
        LEFT JOIN user_follows_hashtags ufh ON h.id = ufh.hashtag_id
        WHERE h.id = :hashtag_id
        GROUP BY h.id, h.tag, h.created_at";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":hashtag_id", $hashtag_id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ?: [
            'tag' => '',
            'created_at' => null,
            'total_posts' => 0,
            'unique_users' => 0, 
            'followers_count' => 0,
            'first_post_date' => null,
            'last_post_date' => null,
            'posts_last_24h' => 0,
            'posts_last_week' => 0
        ];
    }

    // Get most active users for a hashtag
    public function getTopUsers($hashtag_id, $limit = 5) {
        $query = "SELECT u.id, u.username, u.description,
                         COUNT(p.id) as post_count,
                         MAX(p.created_at) as last_post_date
                  FROM users u
                  INNER JOIN posts p ON u.id = p.user_id
                  INNER JOIN post_hashtags ph ON p.id = ph.post_id
                  WHERE ph.hashtag_id = :hashtag_id
                  GROUP BY u.id, u.username, u.description
                  ORDER BY post_count DESC, last_post_date DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":hashtag_id", $hashtag_id, PDO::PARAM_INT);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
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

    // Create hashtag
    public function create($tag) {
        $query = "INSERT IGNORE INTO " . $this->table_name . " (tag) VALUES (:tag)";

        $stmt = $this->conn->prepare($query);
        $tag = trim(strtolower($tag));
        $stmt->bindParam(":tag", $tag);

        if($stmt->execute()) {
            if($stmt->rowCount() > 0) {
                $this->id = $this->conn->lastInsertId();
                $this->tag = $tag;
                return true;
            } else {
                // Hashtag already exists, get its ID
                return $this->getByTag($tag);
            }
        }
        return false;
    }

    // Get hashtag activity timeline (when posts were made with this hashtag)
    public function getActivityTimeline($hashtag_id, $days = 30) {
        $query = "SELECT DATE(p.created_at) as date,
                         COUNT(p.id) as post_count,
                         COUNT(DISTINCT p.user_id) as unique_users
                  FROM posts p
                  INNER JOIN post_hashtags ph ON p.id = ph.post_id
                  WHERE ph.hashtag_id = :hashtag_id
                    AND p.created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                  GROUP BY DATE(p.created_at)
                  ORDER BY date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":hashtag_id", $hashtag_id, PDO::PARAM_INT);
        $stmt->bindParam(":days", $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get similar hashtags based on content similarity
    public function getSimilarHashtags($hashtag_id, $limit = 5) {
        $query = "SELECT h2.id, h2.tag, 
                         COUNT(DISTINCT p2.user_id) as common_users,
                         COUNT(p2.id) as similarity_score
                  FROM hashtags h1
                  CROSS JOIN hashtags h2
                  INNER JOIN post_hashtags ph1 ON h1.id = ph1.hashtag_id
                  INNER JOIN post_hashtags ph2 ON h2.id = ph2.hashtag_id
                  INNER JOIN posts p1 ON ph1.post_id = p1.id
                  INNER JOIN posts p2 ON ph2.post_id = p2.id AND p1.user_id = p2.user_id
                  WHERE h1.id = :hashtag_id 
                    AND h2.id != :hashtag_id2
                    AND h2.tag LIKE CONCAT('%', SUBSTRING(h1.tag, 1, 3), '%')
                  GROUP BY h2.id, h2.tag
                  ORDER BY similarity_score DESC, common_users DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":hashtag_id", $hashtag_id, PDO::PARAM_INT);
        $stmt->bindParam(":hashtag_id2", $hashtag_id, PDO::PARAM_INT);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get hashtags by popularity
    public function getPopularHashtags($limit = 20, $time_period = 'all') {
        $time_condition = '';
        $params = [':limit' => $limit];

        switch($time_period) {
            case 'day':
                $time_condition = 'AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)';
                break;
            case 'week':
                $time_condition = 'AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)';
                break;
            case 'month':
                $time_condition = 'AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)';
                break;
        }

        $query = "SELECT h.id, h.tag, h.created_at,
                         COUNT(DISTINCT ph.post_id) as post_count,
                         COUNT(DISTINCT p.user_id) as unique_users,
                         COUNT(DISTINCT ufh.user_id) as follower_count,
                         MAX(p.created_at) as last_used_date
                  FROM hashtags h
                  LEFT JOIN post_hashtags ph ON h.id = ph.hashtag_id
                  LEFT JOIN posts p ON ph.post_id = p.id
                  LEFT JOIN user_follows_hashtags ufh ON h.id = ufh.hashtag_id
                  WHERE 1=1 {$time_condition}
                  GROUP BY h.id, h.tag, h.created_at
                  HAVING post_count > 0
                  ORDER BY post_count DESC, unique_users DESC, follower_count DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Delete hashtag (only if no posts use it)
    public function delete($hashtag_id) {
        // Check if hashtag is still in use
        $check_query = "SELECT COUNT(*) as usage_count FROM post_hashtags WHERE hashtag_id = :hashtag_id";
        $stmt = $this->conn->prepare($check_query);
        $stmt->bindParam(":hashtag_id", $hashtag_id);
        $stmt->execute();
        
        $usage = $stmt->fetch(PDO::FETCH_ASSOC)['usage_count'];
        
        if($usage > 0) {
            return false; // Cannot delete hashtag that's still in use
        }

        // Delete the hashtag
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :hashtag_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":hashtag_id", $hashtag_id);
        
        return $stmt->execute();
    }

    // Get hashtag growth statistics
    public function getGrowthStats($hashtag_id, $days = 30) {
        $query = "SELECT 
                    COUNT(CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as posts_today,
                    COUNT(CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as posts_this_week,
                    COUNT(CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as posts_this_month,
                    COUNT(CASE WHEN ufh.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 END) as new_followers_today,
                    COUNT(CASE WHEN ufh.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as new_followers_this_week,
                    COUNT(CASE WHEN ufh.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_followers_this_month
                  FROM hashtags h
                  LEFT JOIN post_hashtags ph ON h.id = ph.hashtag_id
                  LEFT JOIN posts p ON ph.post_id = p.id
                  LEFT JOIN user_follows_hashtags ufh ON h.id = ufh.hashtag_id
                  WHERE h.id = :hashtag_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":hashtag_id", $hashtag_id, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
            'posts_today' => 0,
            'posts_this_week' => 0,
            'posts_this_month' => 0,
            'new_followers_today' => 0,
            'new_followers_this_week' => 0,
            'new_followers_this_month' => 0
        ];
    }

    // Get all hashtags with minimal data (for autocomplete/suggestions)
    public function getAllTags($limit = 100) {
        $query = "SELECT id, tag FROM " . $this->table_name . " 
                  ORDER BY tag ASC 
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>