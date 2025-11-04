CREATE DATABASE IF NOT EXISTS match_the_hidden_symbols
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_general_ci;

USE match_the_hidden_symbols;

CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  registered_at DATETIME NOT NULL,
  last_login_at DATETIME,
  total_score INT DEFAULT 0,
  total_games INT DEFAULT 0,
  total_wins INT DEFAULT 0,
  total_time INT DEFAULT 0,
  daily_login_bonus_count INT DEFAULT 0
);

CREATE TABLE statistics (
  stat_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  total_games INT DEFAULT 0,
  total_wins INT DEFAULT 0,
  total_score INT DEFAULT 0,
  total_points INT DEFAULT 0,
  total_time INT DEFAULT 0,
  average_score FLOAT DEFAULT 0,
  average_time FLOAT DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE results (
  result_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  score INT NOT NULL,
  attempts INT NOT NULL,
  time_taken INT NOT NULL,
  moves_used INT NOT NULL,
  played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE leaderboard (
  leaderboard_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  username VARCHAR(50) NOT NULL,
  score INT NOT NULL,
  time_taken INT NOT NULL,
  attempts INT NOT NULL,
  played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE winners (
  winner_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  score INT NOT NULL,
  time_taken INT NOT NULL,
  won_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

ALTER TABLE leaderboard
ADD COLUMN won TINYINT(1) NOT NULL DEFAULT 0 AFTER attempts;
);
