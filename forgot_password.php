<?php
// ============================
// FORGOT_PASSWORD.PHP — StaffCore
// Password Reset Handler
// ============================

session_start();
require_once 'db.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $step = $_POST['step'] ?? '';

    // ---- Step 1: Request reset link ----
    if ($step === 'request') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $error = 'Please enter your email address.';
        } else {
            $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Always show success (prevents email enumeration)
            $success = 'If that email exists, a reset link has been sent. Check your inbox.';

            if ($user) {
                // Clean old tokens
                $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$email]);

                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?,?,?)")
                    ->execute([$email, $token, $expires]);

                // --- Send email ---
                // In production, use PHPMailer or similar.
                // For demo, we just log the link.
                $resetLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                    . '://' . $_SERVER['HTTP_HOST']
                    . dirname($_SERVER['PHP_SELF'])
                    . '/forgot_password.php?token=' . $token;

                $subject = 'StaffCore — Password Reset';
                $message = "Hi {$user['name']},\n\nClick the link below to reset your password (valid 1 hour):\n\n{$resetLink}\n\nIf you did not request this, ignore this email.";
                $headers = 'From: noreply@staffcore.com';

                @mail($email, $subject, $message, $headers);

                // Log it
                $pdo->prepare("INSERT INTO activity_log (user_id,action,target) VALUES (?,?,?)")
                    ->execute([$user['id'], 'Password reset requested', 'auth']);
            }
        }

    // ---- Step 2: Set new password ----
    } elseif ($step === 'reset') {
        $token    = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        if (empty($token) || empty($password)) {
            $error = 'Missing required fields.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            $stmt = $pdo->prepare(
                "SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1"
            );
            $stmt->execute([$token]);
            $reset = $stmt->fetch();

            if (!$reset) {
                $error = 'This reset link is invalid or has expired.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password = ?, locked_until = NULL WHERE email = ?")
                    ->execute([$hash, $reset['email']]);
                $pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?")
                    ->execute([$token]);

                // Clear login attempts
                $pdo->prepare("DELETE FROM login_attempts WHERE email = ?")->execute([$reset['email']]);

                $success = 'Password updated successfully! You can now log in.';

                // Log it
                $user = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $user->execute([$reset['email']]);
                $uid = $user->fetchColumn();
                if ($uid) {
                    $pdo->prepare("INSERT INTO activity_log (user_id,action,target) VALUES (?,?,?)")
                        ->execute([$uid, 'Password reset completed', 'auth']);
                }
            }
        }
    }
}

// GET: show form with token pre-filled
include 'forgot_password.html';
?>