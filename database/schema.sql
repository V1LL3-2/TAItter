-- TAItter Database Schema
-- Create database
CREATE DATABASE taitter_db;
USE taitter_db;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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

-- Mentions table
CREATE TABLE mentions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    mentioned_user_id INT NOT NULL,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (mentioned_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_mention (post_id, mentioned_user_id)
);

-- User follows (for @user likes)
CREATE TABLE user_follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id)
);

-- Hashtag follows
CREATE TABLE hashtag_follows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    hashtag_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hashtag_id) REFERENCES hashtags(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hashtag_follow (user_id, hashtag_id)
);

-- Insert sample data
INSERT INTO users (username, email, password, description) VALUES
('alice', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'AI enthusiast and developer'),
('bob', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Tech blogger and coffee lover'),
('charlie', 'charlie@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Machine learning researcher');

INSERT INTO hashtags (tag) VALUES 
('#ai'), ('#tech'), ('#coding'), ('#javascript'), ('#php'), ('#machinelearning');

INSERT INTO posts (user_id, content) VALUES
(1, 'Just discovered an amazing #ai tool for developers! @bob you should check this out.'),
(2, 'Working on a new #tech blog post about modern web development #javascript #php'),
(3, 'Excited about the latest developments in #machinelearning and #ai research!'),
(1, 'TAItter is the future of social media! #tech #coding'),
(2, '@alice great point about AI tools! The #coding community is amazing.');

-- Link hashtags to posts
INSERT INTO post_hashtags (post_id, hashtag_id) VALUES
(1, 1), -- #ai
(1, 2), -- #tech (assuming we add it)
(2, 2), -- #tech
(2, 4), -- #javascript
(2, 5), -- #php
(3, 6), -- #machinelearning
(3, 1), -- #ai
(4, 2), -- #tech
(4, 3), -- #coding
(5, 3); -- #coding

-- Add mentions
INSERT INTO mentions (post_id, mentioned_user_id) VALUES
(1, 2), -- @bob in post 1
(5, 1); -- @alice in post 5

-- Add some follows
INSERT INTO user_follows (follower_id, following_id) VALUES
(1, 2), -- alice follows bob
(2, 1), -- bob follows alice
(3, 1), -- charlie follows alice
(1, 3); -- alice follows charlie

-- Add hashtag follows
INSERT INTO hashtag_follows (user_id, hashtag_id) VALUES
(1, 1), -- alice follows #ai
(1, 2), -- alice follows #tech
(2, 2), -- bob follows #tech
(2, 4), -- bob follows #javascript
(3, 1), -- charlie follows #ai
(3, 6); -- charlie follows #machinelearning