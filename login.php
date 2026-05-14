<?php
// ============================
// LOGIN.PHP — StaffCore
// Authentication + Rate Limiting
// ============================

session_start();
require_once 'db.php';

$error = '';
const MAX_ATTEMPTS = 5;
const LOCKOUT_MINUTES = 15;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'staff';
    $remember = isset($_POST['remember']);
    $ip       = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        // --- Check if account is locked ---
        $lockStmt = $pdo->prepare("SELECT locked_until FROM users WHERE email = ? AND role = ?");
        $lockStmt->execute([$email, $role]);
        $lockRow = $lockStmt->fetch();

        if ($lockRow && $lockRow['locked_until'] && strtotime($lockRow['locked_until']) > time()) {
            $remaining = ceil((strtotime($lockRow['locked_until']) - time()) / 60);
            $error = "Account locked. Try again in {$remaining} minute(s).";
        } else {
            // --- Count recent failed attempts ---
            $window = date('Y-m-d H:i:s', strtotime('-' . LOCKOUT_MINUTES . ' minutes'));
            $attStmt = $pdo->prepare(
                "SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > ?"
            );
            $attStmt->execute([$email, $window]);
            $attemptCount = (int)$attStmt->fetchColumn();

            if ($attemptCount >= MAX_ATTEMPTS) {
                // Lock the account
                $lockUntil = date('Y-m-d H:i:s', strtotime('+' . LOCKOUT_MINUTES . ' minutes'));
                $pdo->prepare("UPDATE users SET locked_until = ? WHERE email = ?")->execute([$lockUntil, $email]);
                $error = 'Too many failed attempts. Account locked for ' . LOCKOUT_MINUTES . ' minutes.';
            } else {
                // --- Authenticate ---
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1");
                $stmt->execute([$email, $role]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Clear attempts and lock on success
                    $pdo->prepare("DELETE FROM login_attempts WHERE email = ?")->execute([$email]);
                    $pdo->prepare("UPDATE users SET locked_until = NULL WHERE id = ?")->execute([$user['id']]);

                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['user_name']  = $user['name'];
                    $_SESSION['user_role']  = $user['role'];
                    $_SESSION['user_email'] = $user['email'];

                    // Log action
                    $pdo->prepare("INSERT INTO activity_log (user_id,action,target) VALUES (?,?,?)")
                        ->execute([$user['id'], 'User logged in', 'auth']);

                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (7 * 24 * 3600), '/', '', false, true);
                        $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?")->execute([$token, $user['id']]);
                    }

                    if ($user['role'] === 'admin') {
                        header('Location: admin_dashboard.php');
                    } else {
                        header('Location: staff_dashboard.php');
                    }
                    exit;

                } else {
                    // Log failed attempt
                    $pdo->prepare("INSERT INTO login_attempts (email, ip_address) VALUES (?,?)")
                        ->execute([$email, $ip]);

                    $remaining = MAX_ATTEMPTS - $attemptCount - 1;
                    if ($remaining > 0) {
                        $error = "Invalid email, password, or role. {$remaining} attempt(s) remaining.";
                    } else {
                        $lockUntil = date('Y-m-d H:i:s', strtotime('+' . LOCKOUT_MINUTES . ' minutes'));
                        $pdo->prepare("UPDATE users SET locked_until = ? WHERE email = ?")->execute([$lockUntil, $email]);
                        $error = 'Too many failed attempts. Account locked for ' . LOCKOUT_MINUTES . ' minutes.';
                    }
                }
            }
        }
    }
}

include 'login.html';
?>