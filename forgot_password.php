<?php
ob_start();
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";
$success_message = "";
$email_verified = false;
$email = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'verify_email') {
        $email = $_POST["email"];
        
        // Check if email exists
        $sql = "SELECT * FROM customer WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $email_verified = true;
        } else {
            $error_message = "No account found with this email.";
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        $email = $_POST["email"];
        $new_password = $_POST["new_password"];
        $confirm_password = $_POST["confirm_password"];

        if ($new_password !== $confirm_password) {
            $error_message = "Passwords do not match.";
            $email_verified = true;
        } elseif (strlen($new_password) < 8) {
            $error_message = "Password must be at least 8 characters.";
            $email_verified = true;
        } else {
            $passwordHash = password_hash($new_password, PASSWORD_DEFAULT);
            
            $sql = "UPDATE customer SET PasswordHash = ? WHERE Email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $passwordHash, $email);
            
            if ($stmt->execute()) {
                $success_message = "Password has been reset successfully. You can now login with your new password.";
                $email_verified = false;
                $email = "";
            } else {
                $error_message = "Error updating password. Please try again.";
                $email_verified = true;
            }
        }
    }
}
$conn->close();
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="./css/auth.css">
    <link rel="stylesheet" href="./css/top-navigation.css">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            background: #b3b3b356;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .auth-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
            flex: 1;
            width: 100%;
        }

        .container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.12), 
                        0 10px 10px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 600px;
            min-height: 200px;
        }

        .form-container {
            padding: 40px;
            height: 100%;
        }

        form {
            background: #ffffff;
            display: flex;
            flex-direction: column;
            padding: 0 40px;
            height: 100%;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        h1 {
            font-size: 28px;
            margin-bottom: 30px;
            color: #222;
        }

        input {
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            padding: 15px;
            margin: 12px 0;
            width: 100%;
            font-size: 16px;
            border-radius: 5px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #333;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        button {
            border-radius: 25px;
            border: 1px solid #222;
            background-color: #222;
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
            padding: 15px 50px;
            margin-top: 20px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease-in;
            cursor: pointer;
        }

        button:hover {
            background-color: #000;
            border-color: #000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .error-message {
            color: #e74c3c;
            margin: 15px 0;
            font-size: 16px;
            text-align: center;
            background-color: rgba(231, 76, 60, 0.1);
            padding: 10px 15px;
            border-radius: 5px;
            width: 100%;
        }

        .success-message {
            color: #27ae60;
            margin: 15px 0;
            font-size: 16px;
            text-align: center;
            background-color: rgba(39, 174, 96, 0.1);
            padding: 10px 15px;
            border-radius: 5px;
            width: 100%;
        }

        .back-to-login {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            margin-top: 15px;
            display: inline-block;
            transition: color 0.3s ease;
        }

        .back-to-login:hover {
            color: #333;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            form {
                padding: 0 20px;
            }
            
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="auth-wrapper">
    <div class="container">
        <div class="form-container">
            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <?php if (!$email_verified): ?>
                <form action="forgot_password.php" method="POST">
                    <h1>Reset Password</h1>
                    <p>Enter your email address to reset your password</p>
                    <input type="email" name="email" placeholder="Email" required value="<?php echo htmlspecialchars($email); ?>"/>
                    <input type="hidden" name="action" value="verify_email">
                    <button type="submit">Continue</button>
                    <a href="auth.php" class="back-to-login">Back to Login</a>
                </form>
            <?php else: ?>
                <form action="forgot_password.php" method="POST">
                    <h1>Set New Password</h1>
                    <input type="password" name="new_password" placeholder="New Password" required minlength="8"/>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="8"/>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <input type="hidden" name="action" value="reset_password">
                    <button type="submit">Reset Password</button>
                    <a href="auth.php" class="back-to-login">Back to Login</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html> 