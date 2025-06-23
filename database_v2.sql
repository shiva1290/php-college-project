-- Create database
CREATE DATABASE IF NOT EXISTS greyshot DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greyshot;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS reading_history;
DROP TABLE IF EXISTS reactions;
DROP TABLE IF EXISTS upvotes;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    user_id VARCHAR(32) PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    icon_seed VARCHAR(32) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_post_date DATE DEFAULT NULL,
    INDEX username_idx (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Posts table (updated)
CREATE TABLE posts (
    post_id VARCHAR(32) PRIMARY KEY,
    user_id VARCHAR(32) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT FALSE,
    upvotes INT DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX created_idx (created_at DESC),
    INDEX user_post_idx (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments table (updated)
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32),  -- Optional for anonymous comments
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_approved BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX post_approved_idx (post_id, is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reactions table
CREATE TABLE reactions (
    reaction_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    reaction_type ENUM('relate', 'needed', 'thanks') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_reaction (post_id, user_id, reaction_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Upvotes table
CREATE TABLE upvotes (
    post_id VARCHAR(32) NOT NULL,
    user_id VARCHAR(32) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    PRIMARY KEY (post_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reading history table
CREATE TABLE reading_history (
    user_id VARCHAR(32) NOT NULL,
    post_id VARCHAR(32) NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    PRIMARY KEY (user_id, post_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Triggers to update post upvote count
DELIMITER //
CREATE TRIGGER after_upvote_insert
AFTER INSERT ON upvotes
FOR EACH ROW
BEGIN
    UPDATE posts SET upvotes = upvotes + 1 WHERE post_id = NEW.post_id;
END //

CREATE TRIGGER after_upvote_delete
AFTER DELETE ON upvotes
FOR EACH ROW
BEGIN
    UPDATE posts SET upvotes = upvotes - 1 WHERE post_id = OLD.post_id;
END //
DELIMITER ; 