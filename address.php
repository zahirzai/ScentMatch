<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php';

// Redirect to login page if user is not logged in
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

// Fetch user data including address
$email = $_SESSION['email'];
$sql = "SELECT Name, Address FROM customer WHERE Email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle address update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_address'])) {
    $newAddress = trim($_POST['new_address']);

    if (!empty($newAddress)) {
        $updateSql = "UPDATE customer SET Address = ? WHERE Email = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ss", $newAddress, $email);

        if ($updateStmt->execute()) {
            $message = "Address updated successfully!";
            $user['Address'] = $newAddress; // Update local copy
        } else {
            $message = "Error updating address. Please try again.";
        }
        $updateStmt->close();
    } else {
        $message = "Address cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Address | ScentMatch</title>
    <link rel="stylesheet" href="css/top-navigation.css">
    <link rel="stylesheet" href="css/profile.css">
    <style>
        /* Address-specific styles that match profile.php design */
        .address-form {
            margin-top: 30px;
            background-color: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .address-form label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .address-form textarea {
            width: 100%;
            height: 120px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: "Lato", sans-serif;
            font-size: 14px;
            resize: vertical;
            transition: border-color 0.3s ease;
        }
        
        .address-form textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .address-form button {
            background-color: #000;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 15px;
            transition: background-color 0.3s ease;
        }
        
        .address-form button:hover {
            background-color: #333;
        }
        
        .current-address {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .current-address p {
            margin: 0;
            line-height: 1.6;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body class="profile-body">
    <div class="dashboard-container">
        <div class="center">
            <?php include 'sidebar.php'; ?>

            <div class="dashboard-content">
                <h2>My Address</h2>
                
                <?php if (isset($message)): ?>
                    <div class="message <?php echo ($message === "Address updated successfully!") ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="current-address">
                    <h3>Current Address</h3>
                    <?php if (!empty($user['Address'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($user['Address'])); ?></p>
                    <?php else: ?>
                        <p>No address currently saved.</p>
                    <?php endif; ?>
                </div>
                
                <div class="address-form">
                    <h3>Update Address</h3>
                    <form method="POST" action="">
                        <label for="new_address">Enter your new address:</label>
                        <textarea id="new_address" name="new_address" placeholder="Enter your full address including postal code" required><?php 
                            echo isset($_POST['new_address']) ? htmlspecialchars($_POST['new_address']) : ''; 
                        ?></textarea>
                        <button type="submit">Save Address</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add any necessary JavaScript here
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>