CREATE DATABASE IF NOT EXISTS hoopmatch_db;
USE hoopmatch_db;

CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ;

CREATE TABLE barangays (
    id INT(11) NOT NULL AUTO_INCREMENT,
    barangay_name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id)
) ;

CREATE TABLE game_types (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11),
    game_type VARCHAR(10),
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ;

CREATE TABLE userteams (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11),
    team_name VARCHAR(100),
    player_name VARCHAR(100),
    game_type VARCHAR(50),
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ;

CREATE TABLE teams (
    id INT(11) NOT NULL AUTO_INCREMENT,
    userteam_id INT(11),
    team_name VARCHAR(100),
    PRIMARY KEY (id),
    FOREIGN KEY (userteam_id) REFERENCES userteams(id) ON DELETE CASCADE
) ;

CREATE TABLE courts (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11),
    court_name VARCHAR(50),
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ;

CREATE TABLE usercourts (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11),
    court VARCHAR(100),
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE user_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11),
    action ENUM('LOGIN', 'LOGOUT', 'REGISTER'),
    log_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ;

CREATE TABLE matches (
    id INT(11) NOT NULL AUTO_INCREMENT,
    team1_id INT(11),
    team2_id INT(11),
    status ENUM('ready', 'completed'),
    match_time DATETIME,
    PRIMARY KEY (id)
);

CREATE TABLE usersteam (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ;