-- Synkey Nevi 데이터베이스 스키마
-- MySQL/MariaDB용 SQL 스크립트

-- 데이터베이스 생성
CREATE DATABASE IF NOT EXISTS synkey_nevi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE synkey_nevi_db;

-- 관리자 계정 테이블
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB;

-- 승인 상태 테이블
CREATE TABLE IF NOT EXISTS approval_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    status ENUM('check', 'not') NOT NULL DEFAULT 'not',
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- 로그인 시도 기록 테이블 (보안 강화)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    INDEX idx_username_time (username, attempt_time),
    INDEX idx_ip_time (ip_address, attempt_time)
) ENGINE=InnoDB;

-- 활동 로그 테이블 (감사 추적)
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- 기본 관리자 계정 생성
-- 비밀번호: synkey_delete_secure_2024
-- 실제 배포시에는 반드시 비밀번호를 변경하세요!
INSERT INTO admin_users (username, password_hash) VALUES 
('admin', '$2y$10$rYvL3EY.yK7P0VxQJqJHqeL5pQX9/qF.X5WKJ8Zz5LqGzH7VnZLOm');

-- 기본 승인 상태 생성 (거부 상태로 시작)
INSERT INTO approval_status (status, updated_by) VALUES ('not', 1);

-- 데이터베이스 사용자 생성 및 권한 부여
-- 주의: 실제 배포시에는 강력한 비밀번호를 사용하세요!
-- CREATE USER IF NOT EXISTS 'synkey_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
-- GRANT SELECT, INSERT, UPDATE ON synkey_nevi_db.* TO 'synkey_user'@'localhost';
-- FLUSH PRIVILEGES;
