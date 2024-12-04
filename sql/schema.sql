DROP DATABASE IF EXISTS betting_system;
CREATE DATABASE betting_system;

USE betting_system;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100),
    gender ENUM('male', 'female'),
    birth_date DATE,
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active'
);

CREATE TABLE contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    type ENUM('phone', 'email') NOT NULL,
    contact VARCHAR(100) NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE balances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    currency ENUM('EUR', 'USD', 'RUB'),
    amount DECIMAL(10, 2) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    team1 VARCHAR(50),
    team2 VARCHAR(50),
    odds_team1 DECIMAL(4, 2),
    odds_draw DECIMAL(4, 2),
    odds_team2 DECIMAL(4, 2)
);

CREATE TABLE bets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    match_id INT,
    event VARCHAR(255),
    outcome_team VARCHAR(50),
    outcome ENUM('win', 'draw', 'lose'),
    odds DECIMAL(4, 2),
    amount DECIMAL(10, 2),
    currency ENUM('EUR', 'USD', 'RUB'),
    result ENUM('pending', 'win', 'lose') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (match_id) REFERENCES matches(id)
);

-- Insert test data
INSERT INTO users (login, password, name, gender, birth_date, address, status)
VALUES
('user1', '$2y$10$57TRZfVZVb1.FPbhu4iGQeThjFC/vyID0xwJYpmFfe.LqThuRaSr6', 'User One', 'male', '1990-01-01', '123 Main St', 'active'),
('user2', '$2y$10$57TRZfVZVb1.FPbhu4iGQeThjFC/vyID0xwJYpmFfe.LqThuRaSr6', 'User Two', 'female', '1992-02-02', '456 Elm St', 'active');

INSERT INTO contacts (user_id, type, contact)
VALUES
(1, 'phone', '1234567890'),
(1, 'email', 'user1@example.com'),
(2, 'phone', '0987654321'),
(2, 'email', 'user2@example.com');

INSERT INTO balances (user_id, currency, amount)
VALUES
(1, 'USD', 700.00),
(1, 'EUR', 200.00),
(1, 'RUB', 5000.00),
(2, 'RUB', 3000.00),
(2, 'USD', 1000.00),
(2, 'EUR', 500.00);

INSERT INTO matches (team1, team2, odds_team1, odds_draw, odds_team2)
VALUES
('Team 1', 'Team 2', 2.50, 3.05, 3.15),
('Team 3', 'Team 4', 1.45, 3.45, 5.87);