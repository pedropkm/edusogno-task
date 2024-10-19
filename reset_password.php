<?php
session_start();
require_once 'db_connection.php';

$token = $_GET['token'] ?? '';
$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            $user = getUserByResetToken($pdo, $token);
            if ($user && strtotime($user['reset_expires']) > time()) {
                // Update the user's password
                updateUserPassword($pdo, $user['id'], password_hash($new_password, PASSWORD_DEFAULT));
                // Clear the reset token
                clearResetToken($pdo, $user['id']);
                $message = "Your password has been successfully reset. You can now login with your new password.";
            } else {
                $error = "Invalid or expired reset token.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/styles/styles.css">
</head>
<body>
    <header>
        <div class="top">
            <img src="https://yt3.googleusercontent.com/nL-9BX85Bs_XTcQHX_Dhpmd8bta65sIpXeGL_21Ga26EapZzdSjlvOCA3kYs1z6t4ibW9Hrrgg=s900-c-k-c0x00ffffff-no-rj" alt="logo">
        </div>
    </header>
    <main>
        <h2>Reset Your Password</h2>
        <div class="container">
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php else: ?>
                <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                    <span>New Password</span>
                    <input type="password" name="new_password" required>
                    <span>Confirm New Password</span>
                    <input type="password" name="confirm_password" required>
                    <button type="submit">Reset Password</button>
                </form>
            <?php endif; ?>
            <div class="account">
                <p><a href="index.php?page=login">Back to Login</a></p>
            </div>
        </div>
    </main>
</body>
</html>