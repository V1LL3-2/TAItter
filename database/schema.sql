-- TAItter Database Schema - Fixed Version
-- Create database
CREATE DATABASE IF NOT EXISTS taitter;
USE taitter;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Posts table
CREATE TABLE posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    content VARCHAR(144) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
);

-- Hashtags table
CREATE TABLE hashtags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tag VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Post hashtags relationship table
CREATE TABLE post_hashtags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    hashtag_id INT NOT NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (hashtag_id) REFERENCES hashtags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_hashtag (post_id, hashtag_id)
);

-- Post mentions table (fixed name)
CREATE TABLE post_mentions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    mentioned_user_id INT NOT NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (mentioned_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_mention (post_id, mentioned_user_id)
);

-- User likes users table (fixed name to match models)
CREATE TABLE user_likes_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    liker_id INT NOT NULL,
    liked_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (liker_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (liked_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (liker_id, liked_user_id)
);

-- User follows hashtags table (fixed name)
CREATE TABLE user_follows_hashtags (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hashtag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hashtag_id) REFERENCES hashtags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hashtag_follow (user_id, hashtag_id)
);

-- Insert sample data with correct password hashes
INSERT INTO users (username, email, password, description) VALUES
('admin', 'admin@taitter.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator account'),
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Software developer and tech enthusiast'),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'UI/UX Designer passionate about user experience'),
('bob', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tech blogger and coffee lover');

-- Insert hashtags
INSERT INTO hashtags (tag) VALUES 
('TAItter'), ('innovation'), ('tech'), ('collaboration'), ('teamwork'), ('design'), ('development');

-- Insert sample posts
INSERT INTO posts (user_id, content) VALUES
(1, 'Welcome to #TAItter! The future of social media is here. #innovation #tech'),
(2, 'Anyone else excited about the latest @jane_smith designs? #collaboration'),
(3, 'Thanks @john_doe! Working together is always great. #teamwork'),
(1, 'Building the next generation of social platforms. #development #tech');

-- Link hashtags to posts
INSERT INTO post_hashtags (post_id, hashtag_id) VALUES
(1, 1), (1, 2), (1, 3), -- Post 1: TAItter, innovation, tech
(2, 4), -- Post 2: collaboration
(3, 5), -- Post 3: teamwork
(4, 6), (4, 3); -- Post 4: development, tech

-- Add mentions
INSERT INTO post_mentions (post_id, mentioned_user_id) VALUES
(2, 3), -- @jane_smith in post 2
(3, 2); -- @john_doe in post 3

-- Add some sample follows and likes
INSERT INTO user_likes_users (liker_id, liked_user_id) VALUES
(1, 2), (1, 3), (2, 1), (2, 3), (3, 1);

-- Add hashtag follows
INSERT INTO user_follows_hashtags (user_id, hashtag_id) VALUES
(1, 1), (1, 2), (1, 3), (2, 1), (2, 4), (3, 1), (3, 5);