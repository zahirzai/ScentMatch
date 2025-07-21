<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php';

$error_message = "";
$success_message = "";
$valid_token = false;
$email = "";

// Check if token exists and is valid
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    $stmt = $conn->prepare("SELECT Email FROM customer WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $email = $row['Email'];
        $valid_token = true;
    } else {
        $error_message = "Invalid or expired reset token. Please request a new password reset.";
    }
} else {
    $error_message = "No reset token provided.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $valid_token) {
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];
    
    if (strlen($new_password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and clear reset token
        $update_stmt = $conn->prepare("UPDATE customer SET PasswordHash = ?, reset_token = NULL, reset_token_expiry = NULL WHERE Email = ?");
        $update_stmt->bind_param("ss", $password_hash, $email);
        
        if ($update_stmt->execute()) {
            $success_message = "Password has been reset successfully. You can now login with your new password.";
            // Clear the token from the URL
            header("refresh:3;url=auth.php");
        } else {
            $error_message = "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - ScentMatch</title>
    <link rel="stylesheet" href="./css/auth.css">
    <link rel="stylesheet" href="./css/top-navigation.css">
    <style>
        .reset-password-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.12), 
                        0 10px 10px rgba(0,0,0,0.08);
        }
        
        .reset-password-container h1 {
            text-align: center;
            color: #222;
            margin-bottom: 30px;
        }
        
        .reset-password-container p {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="reset-password-container">
        <h1>Reset Password</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($valid_token): ?>
            <form action="reset_password.php?token=<?php echo htmlspecialchars($_GET['token']); ?>" method="POST">
                <input type="password" name="new_password" placeholder="New Password" required minlength="8"/>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="8"/>
                <button type="submit">Reset Password</button>
            </form>
        <?php else: ?>
            <p>Please request a new password reset link.</p>
            <a href="forgot_password.php" class="back-to-login">Request Password Reset</a>
        <?php endif; ?>
    </div>
</body>
</html> 