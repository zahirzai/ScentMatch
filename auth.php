<?php
ob_start();
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php'; 

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        // Handle login
        $email = ($_POST["email"]);
        $password = $_POST["password"];

        if (strpos($email, '@gmail.com') === false) {
            // Admin login
            $sql = "SELECT * FROM admin WHERE name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                if ($password === $row["password"]) {
                    $_SESSION["admin_name"] = $row["name"];
                    $_SESSION["admin_logged_in"] = true;
                    header("Location: ./Admin/admin_page.php");
                    exit();
                } else {
                    $error_message = "Invalid password.";
                }
            } else {
                $error_message = "No admin found with this username.";
            }
        } else {
            // Customer login
            $sql = "SELECT * FROM customer WHERE Email = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                if (password_verify($password, $row["PasswordHash"])) {
                    if ($row['Status'] === 'suspended') {
                        $_SESSION['suspension_message'] = "Your account has been suspended.";
                        if (!empty($row['suspension_reason'])) {
                            $_SESSION['suspension_message'] .= " Reason: " . htmlspecialchars($row['suspension_reason']);
                        }
                        // Stay on the auth page to display the overlay
                        header("Location: auth.php");
                        exit();
                    } else {
                        $_SESSION["customerID"] = $row["CustomerID"];
                        $_SESSION["user_name"] = $row["Name"];
                        $_SESSION["username"] = $row["Username"];
                        $_SESSION["dob"] = $row["DOB"];
                        $_SESSION["phone"] = $row["Phone"];
                        $_SESSION["email"] = $row["Email"];
                        $_SESSION["address"] = $row["Address"];
                        $_SESSION["is_admin"] = false;

                        // Check for return URL in both GET and POST
                        $return_url = isset($_GET['return_url']) ? $_GET['return_url'] : 
                                    (isset($_POST['return_url']) ? $_POST['return_url'] : 'home.php');
                        
                        // Validate the return URL to ensure it's from our domain
                        $parsed_url = parse_url($return_url);
                        $current_domain = $_SERVER['HTTP_HOST'];
                        
                        if (isset($parsed_url['host']) && $parsed_url['host'] === $current_domain) {
                            header("Location: " . $return_url);
                        } else {
                            header("Location: home.php");
                        }
                        exit();
                    }
                } else {
                    $error_message = "Invalid password.";
                }
            } else {
                $error_message = "No user found with this email.";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'register') {
        // Handle registration
        $username = $_POST["username"];
        $phone = $_POST["phone"];
        $email = $_POST["email"];
        $password = $_POST["password"];

        if (strlen($password) < 8) {
            $error_message = "Password must be at least 8 characters.";
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

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
                    
                    // Set all necessary session variables for automatic login
                    $_SESSION["customerID"] = $customerID;
                    $_SESSION["user_name"] = $username;
                    $_SESSION["username"] = $username;
                    $_SESSION["email"] = $email;
                    $_SESSION["phone"] = $phone;
                    $_SESSION["is_admin"] = false;
                    $_SESSION["new_registration"] = true;
                    
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
}
$conn->close();
ob_end_flush();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login & Register</title>
    <link rel="stylesheet" href="./css/auth.css">
    <link rel="stylesheet" href="./css/top-navigation.css">
    <script>
        // Check for return URL in URL parameters
        window.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const returnUrl = urlParams.get('return_url');
            
            if (returnUrl) {
                // Add return URL to all login/register forms
                const forms = document.querySelectorAll('form');
                forms.forEach(form => {
                    const returnInput = document.createElement('input');
                    returnInput.type = 'hidden';
                    returnInput.name = 'return_url';
                    returnInput.value = returnUrl;
                    form.appendChild(returnInput);
                });
            }
        });
    </script>
    <style>
        .auth-page * {
            box-sizing: border-box;
        }

        .auth-page {
            background: #b3b3b356;
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

        .auth-container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 14px 28px rgba(0,0,0,0.12), 
                        0 10px 10px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
            width: 100%;
            max-width: 1200px;
            min-height: 600px;
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
        }

        .sign-in-container {
            left: 0;
            width: 50%;
            z-index: 2;
        }

        .sign-up-container {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 1;
        }

        .auth-container.right-panel-active .sign-in-container {
            transform: translateX(100%);
        }

        .auth-container.right-panel-active .sign-up-container {
            transform: translateX(100%);
            opacity: 1;
            z-index: 5;
        }

        .auth-form {
            background: #ffffff;
            display: flex;
            flex-direction: column;
            padding: 0 80px;
            height: 100%;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .auth-form h1 {
            font-size: 28px;
            margin-bottom: 30px;
            color: #222;
        }

        .auth-form input {
            background: #f5f5f5;
            border: 1px solid #e0e0e0;
            padding: 15px;
            margin: 12px 0;
            width: 100%;
            font-size: 16px;
            border-radius: 5px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .auth-form input:focus {
            outline: none;
            border-color: #333;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }

        .auth-form button {
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

        .auth-form button:hover {
            background-color: #000;
            border-color: #000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .auth-form button:active {
            transform: scale(0.95);
        }

        .auth-form button:focus {
            outline: none;
        }

        .auth-form .ghost {
            background-color: transparent;
            border-color: #ffffff;
        }

        .auth-form .ghost:hover {
            background-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.2);
        }

        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.6s ease-in-out;
            z-index: 100;
        }

        .auth-container.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }

        .overlay {
            background: linear-gradient(135deg, #111 0%, #333 50%, #444 100%);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 0 0;
            color: #ffffff;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .auth-container.right-panel-active .overlay {
            transform: translateX(50%);
        }

        .overlay-panel {
            position: absolute;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0 40px;
            text-align: center;
            top: 0;
            height: 100%;
            width: 50%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .overlay-panel h1 {
            font-size: 32px;
            margin-bottom: 20px;
            color: white;
        }

        .overlay-panel p {
            font-size: 18px;
            margin-bottom: 30px;
            color: rgba(255, 255, 255, 0.9);
        }

        .overlay-left {
            transform: translateX(-20%);
        }

        .auth-container.right-panel-active .overlay-left {
            transform: translateX(0);
        }

        .overlay-right {
            right: 0;
            transform: translateX(0);
        }

        .auth-container.right-panel-active .overlay-right {
            transform: translateX(20%);
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

        .forgot-password {
            color: #666;
            text-decoration: none;
            font-size: 14px;
            margin-top: 15px;
            display: inline-block;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #333;
            text-decoration: underline;
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

        @media (max-width: 768px) {
            .auth-container {
                min-height: 550px;
                margin: 10px;
            }
            
            .auth-form {
                padding: 0 30px;
            }
            
            .overlay-panel {
                padding: 0 30px;
            }
            
            .auth-form h1 {
                font-size: 24px;
            }
            
            .overlay-panel h1 {
                font-size: 28px;
            }
            
            .overlay-panel p {
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .auth-container {
                width: 100%;
            }
            
            .form-container, .overlay-container {
                width: 100%;
                position: relative;
                height: auto;
                left: 0;
            }
            
            .overlay-container {
                display: none;
            }
            
            .sign-in-container, .sign-up-container {
                width: 100%;
            }
            
            .auth-container.right-panel-active .sign-in-container,
            .auth-container.right-panel-active .sign-up-container {
                transform: none;
            }
            
            .sign-up-container {
                opacity: 0;
                display: none;
            }
            
            .auth-container.right-panel-active .sign-up-container {
                display: block;
                opacity: 1;
            }
            
            .auth-container.right-panel-active .sign-in-container {
                display: none;
            }
        }

        /* Overlay Button Styles */
        .overlay-panel .ghost {
            background-color: transparent;
            border: 2px solid #ffffff;
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
            padding: 12px 45px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 25px;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }

        .overlay-panel .ghost:hover {
            background-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 255, 255, 0.2);
        }

        .overlay-panel .ghost:active {
            transform: scale(0.95);
        }

        .overlay-panel .ghost::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: all 0.6s ease;
        }

        .overlay-panel .ghost:hover::before {
            left: 100%;
        }

        .overlay-panel .ghost:focus {
            outline: none;
        }

        /* Responsive styles for overlay buttons */
        @media (max-width: 768px) {
            .overlay-panel .ghost {
                padding: 10px 35px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .overlay-panel .ghost {
                padding: 8px 25px;
                font-size: 12px;
            }
        }

        /* Add to your existing styles */
        .overlay-message {
            display: flex;
            position: fixed;
            z-index: 1001; /* Higher than other modals */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
        }

        .overlay-message-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 90%;
        }

        .overlay-message-content h3 {
            margin-top: 0;
            color: #e74c3c; /* Red color for suspension */
            font-size: 22px;
        }

        .overlay-message-content p {
            margin: 15px 0 25px 0;
            color: #555;
            font-size: 16px;
            line-height: 1.5;
        }

        .overlay-message-content button {
            padding: 10px 20px;
            background-color: #e74c3c; /* Red button */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }

        .overlay-message-content button:hover {
            background-color: #c0392b;
        }

        .suspension-overlay {
            display: flex;
            position: fixed;
            z-index: 1001; /* Higher than other modals */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            animation: fadeInOverlay 0.3s ease-out; /* Add animation */
        }

        .suspension-message {
            background-color: white;
            padding: 40px 30px; /* Increased padding */
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2); /* More prominent shadow */
            max-width: 450px; /* Slightly increased max width */
            width: 90%;
            position: relative; /* Needed for potential future elements */
            animation: slideInModal 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94); /* Add animation */
        }

        /* New style for the icon */
        .suspension-icon {
            font-size: 48px; /* Large icon size */
            color: #e74c3c; /* Red color for warning */
            margin-bottom: 20px; /* Space below the icon */
            display: block; /* Ensure it takes its own line */
        }

        .suspension-message h2 {
            margin-top: 0;
            color: #333; /* Darker color for heading */
            font-size: 24px; /* Slightly larger heading */
            margin-bottom: 15px; /* Space below heading */
        }

        .suspension-message p {
            margin: 0 0 15px 0; /* Standard paragraph spacing */
            color: #555;
            font-size: 16px;
            line-height: 1.5;
        }

        /* Style for secondary text */
        .suspension-message .secondary-text {
            font-size: 14px;
            color: #666;
            margin-top: 10px; /* Space above secondary text */
        }

        .suspension-message .reason {
            margin-top: 20px; /* Space above the reason block */
        }

        /* Style for the inner reason content with background */
        .suspension-message .reason .reason-content {
            background-color: #f8f9fa; /* Light grey background */
            padding: 15px; /* Padding around the text */
            border-radius: 8px; /* Rounded corners */
            border: 1px solid #e9ecef; /* Subtle border */
        }

        .suspension-message .reason strong {
            color: #e74c3c;
        }

        .suspension-message .contact {
            margin-top: 20px; /* Space above contact */
            font-size: 14px;
            color: #777;
        }

        .suspension-actions {
            margin-top: 30px; /* Space above action buttons */
        }

        .suspension-actions .action-btn {
            padding: 12px 25px; /* Larger button padding */
            background-color: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .suspension-actions .action-btn:hover {
            background-color: #c0392b;
        }

        /* Animations */
        @keyframes fadeInOverlay {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideInModal {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Add exit animations */
        @keyframes fadeOutOverlay {
            from { opacity: 1; }
            to { opacity: 0; }
        }

        @keyframes slideOutModal {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(-50px); opacity: 0; }
        }

        /* Add closing classes to apply exit animations */
        .suspension-overlay.closing {
            animation: fadeOutOverlay 0.3s ease-in-out forwards; /* Use forwards to keep final state */
        }

        .suspension-message.closing {
            animation: slideOutModal 0.4s ease-in-out forwards; /* Use forwards to keep final state */
        }
    </style>
</head>
<body class="auth-page">

<?php if (isset($_SESSION['suspension_message'])): ?>
<div class="suspension-overlay" id="suspensionOverlay" style="display: flex;">
    <div class="suspension-message">
        <div class="suspension-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <h2>Account Suspended</h2>
        <p>Your account has been suspended by the administrator.</p>
        <?php if (!empty($_SESSION['suspension_message'])): ?>
            <div class="reason">
                <div class="reason-content">
                    <strong>Reason:</strong><br>
                    <?php echo htmlspecialchars(str_replace('Your account has been suspended. Reason: ', '', $_SESSION['suspension_message'])); // Extract only the reason part ?>
                </div>
            </div>
        <?php endif; ?>
        <p class="secondary-text">During this suspension period, you will not be able to access your account.</p>
        <p class="contact">If you believe this is a mistake, please contact our support team.</p>
        <div class="suspension-actions">
            <button class="action-btn delete-btn" onclick="closeSuspensionOverlay()">OK</button>
        </div>
    </div>
</div>
<?php unset($_SESSION['suspension_message']); // Clear the message after displaying ?>
<?php endif; ?>

<div class="auth-wrapper">
    <div class="auth-container" id="container">
        <div class="form-container sign-up-container">
            <form class="auth-form" action="auth.php" method="POST">
                <h1>Create Account</h1>
                <?php if (!empty($error_message) && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <input type="text" name="username" placeholder="Username" required/>
                <input type="text" name="phone" placeholder="Phone Number" required/>
                <input type="email" name="email" placeholder="Email" required/>
                <input type="password" name="password" placeholder="Password" required minlength="8"/>
                <input type="hidden" name="action" value="register">
                <button type="submit">Register</button>
            </form>
        </div>
        <div class="form-container sign-in-container">
            <form class="auth-form" action="auth.php" method="POST">
                <h1>Sign in</h1>
                <?php if (!empty($error_message) && isset($_POST['action']) && $_POST['action'] === 'login'): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>
                <input type="text" name="email" placeholder="Email" required value="<?php echo isset($_POST['action']) && $_POST['action'] === 'login' ? htmlspecialchars($_POST['email']) : ''; ?>"/>
                <input type="password" name="password" placeholder="Password" required/>
                <input type="hidden" name="action" value="login">
                <button type="submit">Login</button>
                <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
            </form>
        </div>
        <div class="overlay-container">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Already have an account?</h1>
                    <p>Login to continue your fragrance journey!</p>
                    <button class="ghost" id="signIn">Login</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>New here?</h1>
                    <p>Register to discover your perfect scent match!</p>
                    <button class="ghost" id="signUp">Register</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const container = document.getElementById('container');

// Check if we should show the register panel (from URL parameter)
window.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('panel') === 'register') {
        container.classList.add("right-panel-active");
    }
});

signUpButton.addEventListener('click', () => {
    container.classList.add("right-panel-active");
});

signInButton.addEventListener('click', () => {
    container.classList.remove("right-panel-active");
});

// Function to close the suspension overlay
function closeSuspensionOverlay() {
    const modal = document.getElementById('suspensionOverlay');
    const messageBox = modal.querySelector('.suspension-message');
    
    // Apply closing animations
    modal.classList.add('closing');
    messageBox.classList.add('closing');
    
    // Wait for animation to finish before hiding
    const animationDuration = 400; // Match slideOutModal duration
    setTimeout(() => {
        modal.style.display = 'none';
        // Remove closing classes for next time it opens
        modal.classList.remove('closing');
        messageBox.classList.remove('closing');
    }, animationDuration);
}
</script>

</body>
</html>