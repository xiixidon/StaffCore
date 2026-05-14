-- ============================
-- SCHEMA.SQL — StaffCore (FULL)
-- Employee Management System
-- ============================

CREATE DATABASE IF NOT EXISTS staffcore_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE staffcore_db;

-- ---- Users (login accounts) ----
CREATE TABLE users (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(100)          NOT NULL,
    email          VARCHAR(150)          NOT NULL UNIQUE,
    password       VARCHAR(255)          NOT NULL,
    role           ENUM('admin','staff') DEFAULT 'staff',
    phone          VARCHAR(20)           DEFAULT NULL,
    avatar         VARCHAR(255)          DEFAULT NULL,
    remember_token VARCHAR(100)          DEFAULT NULL,
    locked_until   DATETIME              DEFAULT NULL,
    created_at     TIMESTAMP             DEFAULT CURRENT_TIMESTAMP
);

-- ---- Departments ----
CREATE TABLE departments (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    head_id    INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---- Employees ----
CREATE TABLE employees (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT DEFAULT NULL,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    phone         VARCHAR(20),
    department_id INT DEFAULT NULL,
    position      VARCHAR(100),
    salary        DECIMAL(10,2) DEFAULT 0.00,
    status        ENUM('active','inactive','on_leave') DEFAULT 'active',
    hire_date     DATE,
    avatar        VARCHAR(255) DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE SET NULL
);

ALTER TABLE departments
    ADD CONSTRAINT fk_dept_head
    FOREIGN KEY (head_id) REFERENCES employees(id) ON DELETE SET NULL;

-- ---- Attendance ----
CREATE TABLE attendance (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date        DATE NOT NULL,
    check_in    TIME DEFAULT NULL,
    check_out   TIME DEFAULT NULL,
    status      ENUM('present','absent','late','half_day') DEFAULT 'present',
    notes       TEXT DEFAULT NULL,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (employee_id, date)
);

-- ---- Leave Requests ----
CREATE TABLE leave_requests (
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
CREATE TABLE notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    type       VARCHAR(50)  DEFAULT 'info',
    message    VARCHAR(255) NOT NULL,
    link       VARCHAR(255) DEFAULT NULL,
    is_read    TINYINT(1)   DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ---- Password Reset Tokens ----
CREATE TABLE password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(150) NOT NULL,
    token      VARCHAR(100) NOT NULL UNIQUE,
    expires_at DATETIME     NOT NULL,
    used       TINYINT(1)   DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---- Login Attempts ----
CREATE TABLE login_attempts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    email        VARCHAR(150) NOT NULL,
    ip_address   VARCHAR(45),
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---- Activity Log ----
CREATE TABLE activity_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT DEFAULT NULL,
    action      VARCHAR(255) NOT NULL,
    target      VARCHAR(100) DEFAULT NULL,
    target_id   INT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================
-- SAMPLE DATA
-- ============================

-- Admin (password: Admin@1234)
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@staffcore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Staff users (password: Staff@1234)
INSERT INTO users (name, email, password, role) VALUES
('Alice Tan',    'alice@staffcore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Bob Lim',      'bob@staffcore.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Chloe Ng',     'chloe@staffcore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('David Ong',    'david@staffcore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Emma Wong',    'emma@staffcore.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff'),
('Farid Hassan', 'farid@staffcore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff');

-- Departments
INSERT INTO departments (name) VALUES
('Engineering'), ('Human Resources'), ('Marketing'), ('Finance'), ('Operations');

-- Employees linked to staff users
INSERT INTO employees (user_id, name, email, phone, department_id, position, salary, status, hire_date) VALUES
(2, 'Alice Tan',    'alice@staffcore.com',    '012-3456789', 1, 'Senior Engineer',    7500.00, 'active',   '2021-03-15'),
(3, 'Bob Lim',      'bob@staffcore.com',      '011-2345678', 1, 'Frontend Developer', 5800.00, 'active',   '2022-06-01'),
(4, 'Chloe Ng',     'chloe@staffcore.com',    '013-4567890', 2, 'HR Manager',         6500.00, 'active',   '2020-01-10'),
(5, 'David Ong',    'david@staffcore.com',    '014-5678901', 3, 'Marketing Lead',     6000.00, 'on_leave', '2021-09-20'),
(6, 'Emma Wong',    'emma@staffcore.com',     '016-6789012', 4, 'Finance Analyst',    5500.00, 'active',   '2023-02-14'),
(7, 'Farid Hassan', 'farid@staffcore.com',    '017-7890123', 5, 'Operations Head',    7000.00, 'inactive', '2019-07-01');

-- Sample attendance (today)
INSERT INTO attendance (employee_id, date, check_in, check_out, status) VALUES
(1, CURDATE(), '08:55:00', '17:30:00', 'present'),
(2, CURDATE(), '09:10:00', NULL,       'present'),
(3, CURDATE(), '09:32:00', NULL,       'late'),
(4, CURDATE(), NULL,       NULL,       'absent'),
(5, CURDATE(), '08:45:00', '17:00:00', 'present');

-- Sample leave requests
INSERT INTO leave_requests (employee_id, user_id, leave_type, start_date, end_date, reason, status) VALUES
(4, 5, 'annual',   DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'Family vacation', 'pending'),
(1, 2, 'medical',  DATE_SUB(CURDATE(), INTERVAL 2 DAY), CURDATE(),                           'Doctor appointment', 'approved'),
(3, 4, 'emergency', DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'Family emergency', 'pending');

-- Sample notifications for admin
INSERT INTO notifications (user_id, type, message, link) VALUES
(1, 'leave', 'David Ong submitted a leave request', 'leave_requests.php'),
(1, 'leave', 'Chloe Ng submitted a leave request',  'leave_requests.php');

-- Sample activity log
INSERT INTO activity_log (user_id, action, target, target_id) VALUES
(1, 'Added new employee', 'employee', 6),
(1, 'Updated department', 'department', 3),
(1, 'Approved leave request', 'leave', 2);