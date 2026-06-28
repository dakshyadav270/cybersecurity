-- ============================================================
--  CyberShield Database Schema
--  Run this file once in your MySQL/phpMyAdmin to set up the DB
-- ============================================================

CREATE DATABASE IF NOT EXISTS cybershield CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cybershield;

-- ── USERS ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  username        VARCHAR(50)  NOT NULL UNIQUE,
  email           VARCHAR(120) NOT NULL UNIQUE,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('user','admin') DEFAULT 'user',
  is_verified     TINYINT(1)   DEFAULT 0,
  is_banned       TINYINT(1)   DEFAULT 0,
  verify_token    VARCHAR(64)  DEFAULT NULL,
  reset_token     VARCHAR(64)  DEFAULT NULL,
  reset_expires   DATETIME     DEFAULT NULL,
  avatar_initial  CHAR(1)      GENERATED ALWAYS AS (UPPER(LEFT(username,1))) STORED,
  total_score     INT          DEFAULT 0,
  quizzes_taken   INT          DEFAULT 0,
  best_score      INT          DEFAULT 0,
  badge_level     ENUM('Rookie','Defender','Guardian','CyberHero') DEFAULT 'Rookie',
  created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
  last_login      DATETIME     DEFAULT NULL
);

-- ── QUIZ SCORES ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS quiz_scores (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT          NOT NULL,
  score       INT          NOT NULL,
  total_q     INT          NOT NULL DEFAULT 10,
  percentage  DECIMAL(5,2) GENERATED ALWAYS AS (score / total_q * 100) STORED,
  taken_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── CONTACT MESSAGES ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_messages (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT          DEFAULT NULL,
  name        VARCHAR(80)  NOT NULL,
  email       VARCHAR(120) NOT NULL,
  subject     VARCHAR(160) NOT NULL,
  message     TEXT         NOT NULL,
  is_read     TINYINT(1)   DEFAULT 0,
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ── CHAT HISTORY ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chat_history (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT          DEFAULT NULL,
  role        ENUM('user','assistant') NOT NULL,
  message     TEXT         NOT NULL,
  created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ── SITE STATS (daily snapshot) ────────────────────────────
CREATE TABLE IF NOT EXISTS site_stats (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  stat_date       DATE     UNIQUE,
  new_users       INT DEFAULT 0,
  quizzes_taken   INT DEFAULT 0,
  messages_sent   INT DEFAULT 0,
  chat_requests   INT DEFAULT 0
);

-- ── DEFAULT ADMIN ACCOUNT ──────────────────────────────────
-- Password: Admin@1234  (change immediately after setup!)
INSERT IGNORE INTO users (username, email, password_hash, role, is_verified)
VALUES (
  'admin',
  'admin@cybershield.local',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- bcrypt of "Admin@1234"
  'admin',
  1
);
