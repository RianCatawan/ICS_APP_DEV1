-- 1. USERS TABLE (The Core Account Table)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'player',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. TEAMS TABLE
CREATE TABLE teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_name VARCHAR(100) NOT NULL,
    game_type VARCHAR(50),
    created_by VARCHAR(50),
    team_photo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. PLAYERS TABLE (Linked to Users via user_id)
CREATE TABLE players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, 
    student_id VARCHAR(50) UNIQUE,
    full_name VARCHAR(100),
    course VARCHAR(100),
    contact VARCHAR(20),
    position VARCHAR(50),
    skill_level VARCHAR(50),
    active_team_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_player_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_player_team FOREIGN KEY (active_team_id) REFERENCES teams(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. TEAM PLAYERS (Roster Details)
CREATE TABLE team_players (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT,
    player_name VARCHAR(100),
    age INT,
    height VARCHAR(20),
    role VARCHAR(50),
    CONSTRAINT fk_team_roster FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. RESERVATIONS
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team_id INT,
    username VARCHAR(50),
    reservation_date DATE,
    selected_time VARCHAR(50),
    status ENUM('open','matched','completed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_res_team FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 6. MATCH REQUESTS
CREATE TABLE match_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT,
    challenger_team_id INT,
    status VARCHAR(20) DEFAULT 'pending',
    home_score INT DEFAULT 0,
    away_score INT DEFAULT 0,
    winner_id INT NULL,
    final_status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    home_approved TINYINT(1) DEFAULT 0,
    challenger_approved TINYINT(1) DEFAULT 0,
    CONSTRAINT fk_match_res FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_challenger FOREIGN KEY (challenger_team_id) REFERENCES teams(id) ON DELETE CASCADE,
    CONSTRAINT fk_match_winner FOREIGN KEY (winner_id) REFERENCES teams(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 7. MATCH HISTORY
CREATE TABLE match_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT,
    home_score INT,
    away_score INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    winner_id INT NULL,
    CONSTRAINT fk_history_match FOREIGN KEY (match_id) REFERENCES match_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_winner FOREIGN KEY (winner_id) REFERENCES teams(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 8. USER LOGS
CREATE TABLE user_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100),
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Performance Indexes
CREATE INDEX idx_team_id ON reservations(team_id);
CREATE INDEX idx_reservation_id ON match_requests(reservation_id);
CREATE INDEX idx_challenger_team ON match_requests(challenger_team_id);
CREATE INDEX idx_match_id ON match_history(match_id);