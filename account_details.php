<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php';

// Redirect to login page if user is not logged in
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

// Fetch account details
$email = $_SESSION['email'];
$sql = "SELECT Name, Email, Phone, DOB FROM customer WHERE Email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle form submission for account update
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newName = trim($_POST['account_name']);
    $newPhone = trim($_POST['account_phone']);
    $newDob = trim($_POST['account_dob']);
    $newPassword = trim($_POST['account_password']);

    if (!empty($newName)) {
        $updateSql = "UPDATE customer SET Name = ?, Phone = ?, DOB = ?" . 
                    (!empty($newPassword) ? ", PasswordHash = ?" : "") . 
                    " WHERE Email = ?";
        
        $updateStmt = $conn->prepare($updateSql);
        
        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt->bind_param("sssss", $newName, $newPhone, $newDob, $hashedPassword, $email);
        } else {
            $updateStmt->bind_param("ssss", $newName, $newPhone, $newDob, $email);
        }

        if ($updateStmt->execute()) {
            $message = "Account details updated successfully!";
            $_SESSION['user_name'] = $newName; // Update session with new name
            $user['Name'] = $newName; // Update local data
            $user['Phone'] = $newPhone;
            $user['DOB'] = $newDob;
        } else {
            $message = "Error updating account details. Please try again.";
        }
        $updateStmt->close();
    } else {
        $message = "Name cannot be empty.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Details | ScentMatch</title>
    <link rel="stylesheet" href="css/top-navigation.css">
    <link rel="stylesheet" href="css/profile.css">
    <style>
        /* Account Details Specific Styles */
        .account-details-form {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: "Lato", sans-serif;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        .form-group input[readonly] {
            background-color: #f9f9f9;
            color: #777;
        }
        
        .update-btn {
            background-color: #000;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 5px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s ease;
            width: 100%;
        }
        
        .update-btn:hover {
            background-color: #333;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
            text-align: center;
        }
        
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .current-info {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }
        
        .current-info p {
            margin: 12px 0;
            line-height: 1.6;
        }
        
        .current-info strong {
            color:rgb(0, 0, 0);
            width: 150px;
            display: inline-block;
            font-weight: bold;
        }
    </style>
</head>

<body class="profile-body">
    <div class="dashboard-container">
        <div class="center">
            <?php include 'sidebar.php'; ?>

            <div class="dashboard-content">
                <h2>Account Details</h2>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <div class="current-info">
                    <h3>Current Information</h3>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($user['Name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['Email']); ?></p>
                    <?php if (!empty($user['Phone'])): ?>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['Phone']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($user['DOB'])): ?>
                        <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($user['DOB']); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="account-details-form">
                    <h3>Update Information</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="account_name">Full Name</label>
                            <input type="text" id="account_name" name="account_name" 
                                   value="<?php echo htmlspecialchars($user['Name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="account_email">Email</label>
                            <input type="email" id="account_email" name="account_email" 
                                   value="<?php echo htmlspecialchars($user['Email']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="account_phone">Phone Number</label>
                            <input type="tel" id="account_phone" name="account_phone" 
                                   value="<?php echo !empty($user['Phone']) ? htmlspecialchars($user['Phone']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="account_dob">Date of Birth</label>
                            <input type="date" id="account_dob" name="account_dob" 
                                   value="<?php echo !empty($user['DOB']) ? htmlspecialchars($user['DOB']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="account_password">New Password (leave blank to keep current)</label>
                            <input type="password" id="account_password" name="account_password" 
                                   placeholder="Enter new password">
                        </div>
                        
                        <button type="submit" class="update-btn">Update Account Details</button>
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