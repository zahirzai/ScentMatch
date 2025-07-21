<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $phone = $_POST["phone"];
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Validate passwords
    if (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Check if the email is already registered
        $email_check_stmt = $conn->prepare("SELECT COUNT(*) FROM customer WHERE Email = ?");
        $email_check_stmt->bind_param("s", $email);
        $email_check_stmt->execute();
        $email_check_stmt->bind_result($email_count);
        $email_check_stmt->fetch();
        $email_check_stmt->close();

        if ($email_count > 0) {
            $error_message = "Email is already registered.";
        } else {
            $stmt = $conn->prepare("INSERT INTO customer (Username, Phone, Email, PasswordHash, PreferencesCompleted) VALUES (?, ?, ?, ?, 0)");
            $stmt->bind_param("ssss", $username, $phone, $email, $passwordHash);

            if ($stmt->execute()) {
                $customerID = $stmt->insert_id;

                // Automatically log the user in
                $_SESSION["CustomerID"] = $customerID;
                $_SESSION["user_name"] = $username;
                $_SESSION["email"] = $email;

                // Redirect to question.php
                header("Location: question.php");
                exit();
            } else {
                $error_message = "Error: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="css/top-navigation.css">
    <link rel="stylesheet" href="css/login.css">
    
</head>

<body>
    <div class="form-wrapper">
        <h1>Register</h1>
        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="" method="POST" class="form-box">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>

            <label for="phone">Phone Number:</label>
            <input type="text" id="phone" name="phone" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <span class="btn-Register">
                <button type="submit" class="button btnRegis">Register</button>
            </span>
        </form>
    </div>
</body>

</html>
