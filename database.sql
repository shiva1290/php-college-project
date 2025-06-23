-- Create database
CREATE DATABASE IF NOT EXISTS greyshot DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE greyshot;

-- Posts table
CREATE TABLE posts (
    post_id VARCHAR(32) PRIMARY KEY,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    access_token VARCHAR(64) NOT NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    INDEX created_idx (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments table
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id VARCHAR(32) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_approved BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    FOREIGN KEY (post_id) REFERENCES posts(post_id),
    INDEX post_approved_idx (post_id, is_approved)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limiting table
CREATE TABLE rate_limits (
    ip_address VARCHAR(45) PRIMARY KEY,
    last_post_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    post_count INT DEFAULT 1,
    INDEX ip_time_idx (ip_address, last_post_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 