<<<<<<< HEAD
-- ============================
-- SCHEMA_UPDATES.SQL — StaffCore
-- Run this on top of your existing schema.sql
-- ============================

USE staffcore_db;

-- ---- Leave Requests ----
CREATE TABLE IF NOT EXISTS leave_requests (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    employee_id  INT NOT NULL,
    user_id      INT NOT NULL,
    leave_type   ENUM('annual','medical','emergency','unpaid') DEFAULT 'annual',
    start_date   DATE NOT NULL,
    end_date     DATE NOT NULL,
    reason       TEXT,
    status       ENUM('pending','approved','rejected') DEFAULT 'pending',
    reviewed_by  INT DEFAULT NULL,
    reviewed_at  TIMESTAMP NULL DEFAULT NULL,
    reject_note  VARCHAR(255) DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id)     ON DELETE SET NULL
);

-- ---- Notifications ----
CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,              -- recipient (admin)
    type       VARCHAR(50) DEFAULT 'info',
    message    VARCHAR(255) NOT NULL,
    link       VARCHAR(255) DEFAULT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---- Password Reset Tokens ----
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL,
    token      VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    used       TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---- Login Attempts ----
CREATE TABLE IF NOT EXISTS login_attempts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add locked_until to users if not exists
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS locked_until DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL;

-- Sample staff user linked to Alice
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Alice Tan',    'alice@staffcore.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Bob Lim',      'bob@staffcore.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Chloe Ng',     'chloe@staffcore.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');

-- Link employees to user accounts
UPDATE employees e
JOIN users u ON u.email = e.email
SET e.user_id = u.id
=======
-- ============================
-- SCHEMA_UPDATES.SQL — StaffCore
-- Run this on top of your existing schema.sql
-- ============================

USE staffcore_db;

-- ---- Leave Requests ----
CREATE TABLE IF NOT EXISTS leave_requests (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    employee_id  INT NOT NULL,
    user_id      INT NOT NULL,
    leave_type   ENUM('annual','medical','emergency','unpaid') DEFAULT 'annual',
    start_date   DATE NOT NULL,
    end_date     DATE NOT NULL,
    reason       TEXT,
    status       ENUM('pending','approved','rejected') DEFAULT 'pending',
    reviewed_by  INT DEFAULT NULL,
    reviewed_at  TIMESTAMP NULL DEFAULT NULL,
    reject_note  VARCHAR(255) DEFAULT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id)     ON DELETE SET NULL
);

-- ---- Notifications ----
CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,              -- recipient (admin)
    type       VARCHAR(50) DEFAULT 'info',
    message    VARCHAR(255) NOT NULL,
    link       VARCHAR(255) DEFAULT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---- Password Reset Tokens ----
CREATE TABLE IF NOT EXISTS password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL,
    token      VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    used       TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---- Login Attempts ----
CREATE TABLE IF NOT EXISTS login_attempts (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add locked_until to users if not exists
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS locked_until DATETIME DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL;

-- Sample staff user linked to Alice
INSERT IGNORE INTO users (name, email, password, role) VALUES
('Alice Tan',    'alice@staffcore.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Bob Lim',      'bob@staffcore.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Chloe Ng',     'chloe@staffcore.com',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');

-- Link employees to user accounts
UPDATE employees e
JOIN users u ON u.email = e.email
SET e.user_id = u.id
>>>>>>> 98e1e05841e4235727ebf4e70697bce65fb3fe24
WHERE e.user_id IS NULL;