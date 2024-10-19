<?php
require_once 'db_connection.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';
$error = '';
$success = '';

if (empty($token)) {
    $error = "No reset token provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($token)) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, expires FROM password_reset_tokens WHERE token = ? AND expires > NOW()");
            $stmt->execute([$token]);
            $result = $stmt->fetch();

            if ($result) {
                $user_id = $result['user_id'];
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->execute([$hashed_password, $user_id]);

                $delete_stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
                $delete_stmt->execute([$token]);

                $success = "Your password has been successfully reset. You can now log in with your new password.";
            } else {
                $error = "Invalid or expired token.";
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
    <title>Reset Password - Edusogno</title>
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
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
                <p><a href="index.php?page=login">Go to Login</a></p>
            <?php elseif (!empty($token)): ?>
                <form action="reset_password.php?token=<?php echo htmlspecialchars(urlencode($token)); ?>" method="POST">
                    <span>Enter your new password</span>
                    <input type="password" name="new_password" required>
                    <span>Confirm your new password</span>
                    <input type="password" name="confirm_password" required>
                    <button type="submit">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>