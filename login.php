<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php'; 
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = ($_POST["email"]);
    $password = $_POST["password"];

    // Check if email is a Gmail address for customers
    if (strpos($email, '@gmail.com') === false) {
        // Admin login check
        $sql = "SELECT * FROM admin WHERE name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email); // Admins use name as the login credential
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();

            // Verify admin password
            if ($password === $row["password"]) {
                // Store admin information in session
                $_SESSION["admin_name"] = $row["name"];
                $_SESSION["admin_logged_in"] = true;

                // Redirect to admin page
                header("Location: ./Admin/admin_page.php");
                exit();
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "No admin found with this username.";
        }
    } else {
        // Customer login check
        $sql = "SELECT * FROM customer WHERE Email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email); // Bind email to the query
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();

            // Verify the password
            if (password_verify($password, $row["PasswordHash"])) {
                // Store customer information in session
                $_SESSION["customerID"] = $row["CustomerID"]; // Fix: Store CustomerID
                $_SESSION["user_name"] = $row["Name"];
                $_SESSION["username"] = $row["Username"];
                $_SESSION["dob"] = $row["DOB"];
                $_SESSION["phone"] = $row["Phone"];
                $_SESSION["email"] = $row["Email"];
                $_SESSION["address"] = $row["Address"];
                $_SESSION["is_admin"] = false;

                // Redirect to homepage
                header("Location: home.php");
                exit();
            } else {
                $error_message = "Invalid password.";
            }
        } else {
            $error_message = "No user found with this email.";
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
    <title>Login</title>
    <link rel="stylesheet" href="./css/login.css">
    <link rel="stylesheet" href="./css/top-navigation.css">
</head>

<body class="login-page">
    <div class="form-wrapper">
        <h1>Login</h1>
        <?php if (isset($error_message)): ?>
            <p style="color: red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST" class="form-box">
            <label for="email">Email:</label>
            <input type="text" id="email" name="email" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <span class="btn-Login">
                <button type="submit" class="button btnLogin">Login</button>
            </span>
            <p style="text-align: center; margin-top: 15px;">
                Don't have an account?
                <a href="register.php" style="color: #007bff; text-decoration: none;">Register</a>
            </p>
        </form>
    </div>
</body>

</html>