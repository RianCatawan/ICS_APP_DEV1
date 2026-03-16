-- Create Database
CREATE DATABASE IF NOT EXISTS university_hoops;
USE university_hoops;

-- 1. Users Table (Base table for authentication)
CREATE TABLE users (
    id INT(11) NOT NULL AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- 2. Teams Table
CREATE TABLE teams (
    id INT(11) NOT NULL AUTO_INCREMENT,
    team_name VARCHAR(100) NOT NULL,
    game_type VARCHAR(20) DEFAULT NULL,
    created_by VARCHAR(50) NOT NULL, -- Links to users.username
    team_photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- 3. Players Table
CREATE TABLE players (
    id INT(11) NOT NULL AUTO_INCREMENT,
    student_id VARCHAR(50) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    course VARCHAR(100) DEFAULT NULL,
    contact VARCHAR(20) DEFAULT NULL,
    position VARCHAR(50) DEFAULT NULL,
    skill_level VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    active_team_id INT(11) DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_active_team FOREIGN KEY (active_team_id) REFERENCES teams(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Team Players Table (Junction table for rosters)
CREATE TABLE team_players (
    id INT(11) NOT NULL AUTO_INCREMENT,
    team_id INT(11) NOT NULL,
    player_name VARCHAR(100) NOT NULL,
    age INT(11) DEFAULT NULL,
    height VARCHAR(20) DEFAULT NULL,
    role VARCHAR(50) DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_team_id FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Reservations Table
CREATE TABLE reservations (
    id INT(11) NOT NULL AUTO_INCREMENT,
    team_id INT(11) NOT NULL,
    username VARCHAR(50) NOT NULL,
    reservation_date DATE NOT NULL,
    selected_time VARCHAR(50) NOT NULL,
    status ENUM('open', 'matched', 'completed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_res_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. Match Requests Table
CREATE TABLE match_requests (
    id INT(11) NOT NULL AUTO_INCREMENT,
    reservation_id INT(11) NOT NULL,
    challenger_team_id INT(11) NOT NULL,
    status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    home_approved TINYINT(1) DEFAULT 0,
    challenger_approved TINYINT(1) DEFAULT 0,
    final_status ENUM('pending', 'confirmed') DEFAULT 'pending',
    home_score INT(11) DEFAULT 0,
    away_score INT(11) DEFAULT 0,
    winner_id INT(11) DEFAULT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_match_res FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    CONSTRAINT fk_challenger_team FOREIGN KEY (challenger_team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB;