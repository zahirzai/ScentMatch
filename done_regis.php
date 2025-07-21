<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");

// Ensure the database connection is established
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure the session is active, and the customer has completed registration
if (!isset($_SESSION['customerID']) || !isset($_SESSION['user_name'])) {
    // If session variables are missing, attempt to fetch them from the database
    if (isset($_SESSION['customerID'])) {
        $customerID = $_SESSION['customerID'];
        $stmt = $conn->prepare("SELECT user_name FROM customer WHERE CustomerID = ?");
        $stmt->bind_param("i", $customerID);
        $stmt->execute();
        $stmt->bind_result($userName);
        $stmt->fetch();
        $stmt->close();

        if ($userName) {
            // Set session variables
            $_SESSION['user_name'] = $userName;
        } else {
            // Redirect to login if user not found
            header("Location: login.php");
            exit();
        }
    } else {
        // Redirect to login if CustomerID is missing
        header("Location: login.php");
        exit();
    }
}

// Close database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Successful</title>
    <link rel="stylesheet" href="css/top-bar.css">
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-wrapper {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .form-wrapper .icon {
            font-size: 80px;
            color: #4caf50;
            margin-bottom: 20px;
        }

        .form-wrapper h1 {
            font-size: 2.5em;
            color: #4caf50;
            margin-bottom: 20px;
        }

        .form-wrapper p {
            font-size: 1.2em;
            color: #555;
            margin-bottom: 30px;
        }

        .btn {
            background-color: black;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: rgb(105, 105, 105);
        }
    </style>
</head>

<body>
    <div class="form-wrapper">
        <i class="fas fa-check-circle icon"></i>
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
        <p>Your registration has been completed successfully. Thank you for joining our platform.</p>
        <a href="login.php" class="btn">Go to Homepage</a>
    </div>
</body>

</html>
