<?php
ob_start();

session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php';


// Check database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_name'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['customerID'];
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'to_ship';

// ================== HANDLE REVIEW SUBMISSION ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_submit'])) {
    // Get form data
    $orderID = (int)$_POST['order_id'];
    $productID = (int)$_POST['product_id'];
    $rating = (int)$_POST['rating'];
    // Use htmlspecialchars for review text to prevent XSS, then real_escape_string for SQL safety
    $reviewText = $conn->real_escape_string(htmlspecialchars(trim($_POST['review_text'])));
    
    // Validate required fields (excluding images)
    if (empty($orderID) || empty($productID) || empty($rating) || $reviewText === '') { // Check reviewText explicitly for empty string after trimming
        $_SESSION['error_message'] = "Please fill in all required fields (Rating and Review Text)";
        header("Location: order.php?tab=completed");
        exit();
    }
    
    // Verify the order belongs to the customer and is completed
    $verifySql = "SELECT OrderID FROM manage_order 
                 WHERE OrderID = ? AND CustomerID = ? AND ShipmentStatus = 'Completed'";
    $verifyStmt = $conn->prepare($verifySql);
    
    if ($verifyStmt === false) {
         $_SESSION['error_message'] = "Database error preparing verification: " . $conn->error;
         header("Location: order.php?tab=completed");
         exit();
    }

    $verifyStmt->bind_param("ii", $orderID, $customerID);
    
    if (!$verifyStmt->execute()) {
        $_SESSION['error_message'] = "Database error executing verification: " . $verifyStmt->error;
        header("Location: order.php?tab=completed");
        exit();
    }
    
    $verifyResult = $verifyStmt->get_result();
    
    if ($verifyResult->num_rows === 0) {
        $_SESSION['error_message'] = "Invalid order or order not completed";
        header("Location: order.php?tab=completed");
        exit();
    }
     $verifyStmt->close(); // Close statement after use
    
    // Handle image upload
    $imageString = null;
        $uploadDir = 'uploads/reviews/';
    $uploadedImages = [];

    // Check if files were actually uploaded for 'review_images'
    if (isset($_FILES['review_images']) && is_array($_FILES['review_images']['tmp_name'])) {
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $_SESSION['error_message'] = "Failed to create upload directory: " . error_get_last()['message'];
                header("Location: order.php?tab=completed");
                exit();
            }
             // Add debugging for directory creation success
            echo "DEBUG: Upload directory created successfully: " . $uploadDir . "<br>";
        }
         // Add debugging for directory existence
        echo "DEBUG: Upload directory exists: " . $uploadDir . "<br>";
        
        // Process each file
        foreach ($_FILES['review_images']['tmp_name'] as $key => $tmpName) {
             // Add debugging for each file input
            echo "<br>DEBUG: Processing file input key: " . $key . "<br>";
            echo "DEBUG: Temp name: " . $tmpName . "<br>";
            echo "DEBUG: Error code: " . $_FILES['review_images']['error'][$key] . "<br>";

            // Check if file was uploaded without errors and is not empty
            if ($_FILES['review_images']['error'][$key] === UPLOAD_ERR_OK && !empty($tmpName)) {
                // Check file type using fileinfo or getimagesize for better security than just mime_content_type
                $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
                $fileType = finfo_file($fileInfo, $tmpName);
                finfo_close($fileInfo);

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                 // Add debugging for file type and size checks
                echo "DEBUG: File type: " . $fileType . "<br>";
                echo "DEBUG: File size: " . $_FILES['review_images']['size'][$key] . "<br>";
                
                if (!in_array($fileType, $allowedTypes)) {
                    // Optionally log skipped file type
                    echo "DEBUG: Skipped invalid file type: " . $fileType . "<br>";
                    continue; // Skip invalid files
                }
                
                // Check file size (max 2MB)
                if ($_FILES['review_images']['size'][$key] > 2097152) {
                     // Optionally log skipped file size
                    echo "DEBUG: Skipped file due to size: " . $_FILES['review_images']['size'][$key] . "<br>";
                    continue;
                }
                
                // Generate unique filename
                $fileExtension = pathinfo($_FILES['review_images']['name'][$key], PATHINFO_EXTENSION);
                $fileName = uniqid('review_', true) . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                 // Add debugging before moving file
                echo "DEBUG: Attempting to move " . $tmpName . " to " . $targetPath . "<br>";

                // Move the file and check for success
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedImages[] = $conn->real_escape_string($fileName); // Escape filename for database
                     // Add debugging for successful move
                    echo "DEBUG: Successfully moved file: " . $fileName . "<br>"; // More specific echo
                } else {
                     // Log move error
                    $error_info = error_get_last();
                    $error_message = isset($error_info['message']) ? $error_info['message'] : 'Unknown error';
                    echo "DEBUG: Failed to move uploaded file from " . $tmpName . " to " . $targetPath . ". Error: " . $error_message . "<br>";
                    // Optionally add a user error message, but skipping the file is safer
                }
            } else {
                // Log upload errors if any
                 if ($_FILES['review_images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                    echo "DEBUG: File upload error for key " . $key . ": " . $_FILES['review_images']['error'][$key] . "<br>";
                 } else {
                     echo "DEBUG: File input was empty for key " . $key . "<br>";
                 }
            }
        }
        
        // Create comma-separated string if we have successfully uploaded images
        if (!empty($uploadedImages)) {
            $imageString = implode(',', $uploadedImages);
             // Add debugging for generated imageString
            echo "DEBUG: Generated Image String: " . $imageString . "<br>";
        } else if (isset($_FILES['review_images']) && $_FILES['review_images']['error'][0] !== UPLOAD_ERR_NO_FILE) { // Check if files were submitted at all (and not just empty input)
             // If files were submitted but none were valid/uploaded, show an error (optional)
             // $_SESSION['error_message'] = "No valid images were uploaded.";
             // header("Location: order.php?tab=completed");
             // exit();
             // Add debugging if no valid images were uploaded
             echo "DEBUG: No valid images were uploaded or processed.<br>";
        } else {
             // Add debugging if $_FILES['review_images'] was set but tmp_name was not an array or empty
             echo "DEBUG: Review images file input was set, but no files were processed or empty.<br>";
        }
    }
     // Add debugging if $_FILES['review_images'] was not set
    else {
         echo "DEBUG: Review images file input was not set. No files uploaded via form.<br>";
    }
    
     // Final check of imageString before insertion
    echo "DEBUG: Final Image String before DB insert: " . ($imageString ?? 'NULL') . "<br>";
    
    // Check for existing review
    $checkSql = "SELECT ReviewID FROM reviews 
                WHERE OrderID = ? AND ProductID = ? AND CustomerID = ?";
    $checkStmt = $conn->prepare($checkSql);

    if ($checkStmt === false) {
        $_SESSION['error_message'] = "Database error preparing review check: " . $conn->error;
        header("Location: order.php?tab=completed");
        exit();
    }

    $checkStmt->bind_param("iii", $orderID, $productID, $customerID);
    
    if (!$checkStmt->execute()) {
        $_SESSION['error_message'] = "Database error executing review check: " . $checkStmt->error;
        header("Location: order.php?tab=completed");
        exit();
    }
    
    if ($checkStmt->get_result()->num_rows > 0) {
        $_SESSION['error_message'] = "You've already reviewed this product for this order."; // More specific message
        header("Location: order.php?tab=completed");
        exit();
    }
     $checkStmt->close(); // Close statement after use
    
    // Insert the review into the reviews table
    $insertSql = "INSERT INTO reviews (OrderID, ProductID, CustomerID, Rating, ReviewText, ReviewImages, CreatedAt) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insertSql);
    
    if (!$stmt) {
        $_SESSION['error_message'] = "Database error preparing review insert: " . $conn->error;
        header("Location: order.php?tab=completed");
        exit();
    }
    
    // Bind parameters, including the imageString (which can be null)
    $stmt->bind_param("iiisss", $orderID, $productID, $customerID, $rating, $reviewText, $imageString);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Thank you for your review!";
    } else {
        // Log specific SQL error
        error_log("SQL Error inserting review: " . $stmt->error);
        echo "DEBUG: SQL Error inserting review: " . $stmt->error . "<br>"; // Echo SQL error
        $_SESSION['error_message'] = "Failed to submit review. Please try again. Error: " . $stmt->error;
    }
    
    $stmt->close();
    header("Location: order.php?tab=completed");
    exit();
}

// ================== HANDLE ORDER RECEIVED ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && !isset($_POST['cancel_action'])) {
    $orderID = $_POST['order_id'];
    
    // Verify order belongs to customer and is 'Delivered'
    $verify_stmt = $conn->prepare("SELECT CustomerID, ShipmentStatus FROM manage_order WHERE OrderID = ?");
    $verify_stmt->bind_param("i", $orderID);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $order_data = $verify_result->fetch_assoc();

    if ($verify_result->num_rows === 0 || $order_data['CustomerID'] != $customerID) {
        $_SESSION['error_message'] = "Invalid order or permission denied";
        header("Location: order.php?tab=to_receive");
        exit();
    }
    
    if ($order_data['ShipmentStatus'] !== 'Delivered') {
        $_SESSION['error_message'] = "Order cannot be marked as received yet";
        header("Location: order.php?tab=to_receive");
        exit();
    }
    
    // Update to 'Completed'
    $updateSql = "UPDATE manage_order SET ShipmentStatus = 'Completed' WHERE OrderID = ? AND CustomerID = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ii", $orderID, $customerID);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Order marked as completed!";
    } else {
        $_SESSION['error_message'] = "Failed to update order status: " . $conn->error;
    }
    $stmt->close();
    header("Location: order.php?tab=to_receive");
    exit();
}

// ================== HANDLE ORDER CANCELLATION ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_action'])) {
    $orderID = $_POST['order_id'];
    $currentTab = $_POST['current_tab'] ?? 'to_ship'; // Get current tab from form
    
    // Verify order belongs to customer and is 'Preparing'
    $verify_stmt = $conn->prepare("SELECT CustomerID, ShipmentStatus FROM manage_order WHERE OrderID = ?");
    $verify_stmt->bind_param("i", $orderID);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    $order_data = $verify_result->fetch_assoc();

    if ($verify_result->num_rows === 0 || $order_data['CustomerID'] != $customerID) {
        $_SESSION['error_message'] = "Invalid order or permission denied";
        header("Location: order.php?tab=" . $currentTab);
        exit();
    }
    
    if ($order_data['ShipmentStatus'] !== 'Preparing') {
        $_SESSION['error_message'] = "Order can only be cancelled if it is in 'Preparing' status";
        header("Location: order.php?tab=" . $currentTab);
        exit();
    }
    
    // Update to 'Cancelled' instead of deleting
    $updateSql = "UPDATE manage_order SET ShipmentStatus = 'Cancelled' WHERE OrderID = ? AND CustomerID = ?";
    $stmt = $conn->prepare($updateSql);
    $stmt->bind_param("ii", $orderID, $customerID);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Order has been cancelled successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to cancel order: " . $conn->error;
    }
    $stmt->close();
    header("Location: order.php?tab=" . $currentTab);
    exit();
}

// ================== FETCH ORDERS ==================
$statusMap = [
    'to_ship'    => "ShipmentStatus IN ('Preparing', 'Shipped') AND ShipmentStatus != 'Cancelled' AND PaymentStatus = 'Paid'",
    'to_receive' => "ShipmentStatus = 'Delivered' AND PaymentStatus = 'Paid'",
    'completed'  => "ShipmentStatus = 'Completed' AND PaymentStatus = 'Paid'"
];

$statusCondition = $statusMap[$currentTab] ?? $statusMap['to_ship'];

$sql = "SELECT mo.OrderID, mo.ProductID, mo.Quantity, mo.Subtotal, mo.TotalPrice, 
               mo.ShipmentStatus, mo.Paid_time, mo.TrackingNumber, mo.Delivery_date,
               mo.DeliveryType, p.product_id, p.product_name, p.product_image
        FROM manage_order mo
        JOIN products p ON mo.ProductID = p.product_id
        WHERE mo.CustomerID = ? AND {$statusCondition}
        ORDER BY mo.Paid_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customerID);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders | ScentMatch</title>
    <link rel="stylesheet" href="css/top-navigation.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/order.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh; /* Ensure body is at least viewport height */
            margin: 0; /* Remove default body margin */
        }
        
        .dashboard-container {
            flex-grow: 1; /* Make the main content area grow */
            /* Ensure it doesn't overflow if content is very tall */
            /* overflow-y: auto; */ /* Consider if needed for very long content */
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            justify-content: center;
            align-items: center;
        }

        /* Add overlay specifically for the Receive Confirmation Modal */
        #receiveConfirmationModal {
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(2px);
        }

        /* Add overlay specifically for the Cancel Confirmation Modal */
        #cancelConfirmationModal {
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(2px);
        }

        .modal-content {
            background-color: #ffffff;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .warning-header {
            background-color: #fff3cd;
            color: #856404;
            border-radius: 12px 12px 0 0;
        }

        .confirm-header {
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 12px 12px 0 0;
        }

        .modal-header i {
            font-size: 24px;
        }

        .modal-header h3 {
            margin: 0;
            flex-grow: 1;
        }

        .close-modal {
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
            color: inherit;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .close-modal:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 25px;
            text-align: center;
        }

        .warning-icon, .confirm-icon {
            font-size: 48px;
            margin-bottom: 20px;
            color: #856404;
        }

        .confirm-icon {
            color: #721c24;
        }

        .warning-message, .confirm-message {
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .warning-text {
            color: #dc3545;
            font-weight: bold;
            margin-top: 10px;
        }

        .warning-checkbox {
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .warning-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .warning-checkbox label {
            cursor: pointer;
            user-select: none;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }

        .secondary-button {
            background-color: #6c757d;
            color: white;
        }

        .secondary-button:hover {
            background-color: #5a6268;
        }

        .warning-button {
            background-color: #ffc107;
            color: #000;
        }

        .warning-button:hover {
            background-color: #e0a800;
        }

        .danger-button {
            background-color: #dc3545;
            color: white;
        }

        .danger-button:hover {
            background-color: #c82333;
        }

        .button i {
            font-size: 16px;
        }

        /* Add hover effects */
        .button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .button:active {
            transform: translateY(0);
        }

        /* Centered Notification Styles */
        .notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #4CAF50;
            color: #ffffff;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 9999;
            animation: fadeIn 0.3s ease-out;
            text-align: center;
        }

        .notification.error {
            background: #f44336;
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .notification-content i {
            color: #ffffff;
            font-size: 20px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translate(-50%, -40%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
            to {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
        }

        /* Overlay for notification */
        .notification-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 9998;
            animation: fadeIn 0.3s ease-in-out forwards;
        }

        /* Add these styles for the review modal */
        .rating-input {
            margin-bottom: 20px;
        }

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 30px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }

        .star-rating input:checked ~ label,
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: #ffd700;
        }

        .review-text {
            margin-bottom: 20px;
        }

        .review-text textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }

        .image-upload {
            margin-bottom: 20px;
            /* position: relative; Removed as children will be flex items */
        }

        .upload-area {
            border-radius: 8px;
            /* Padding added to the container */
            padding: 10px; /* Add padding inside the dashed area */
            text-align: center;
            cursor: pointer;
            transition: border-color 0.3s;
            width: 430px; /* Make the main area wider */
            height: 150px; /* Keep the height */
            display: flex; /* Use flexbox to arrange children */
            /* Remove flex-wrap to make items display horizontally */
            /* flex-wrap: wrap; */
            align-items: center; /* Center items vertically in the row */
            justify-content: flex-start; /* Align items to the start */
            gap: 10px; /* Add gap between flex items */
            margin: 0;
            position: relative; /* Needed for positioning */
            /* Change overflow to handle horizontal scrolling */
            overflow-x: auto;
            overflow-y: hidden; /* Hide vertical scrollbar if horizontal is needed */
        }

        /* Style for the upload area when it has content, adjust padding if needed */
        .upload-area.has-content {
            /* Adjust padding or height if content fills the area differently */
        }

        .upload-area:hover {
            border-color: #007bff;
        }

        /* Adjust icon size */
        .upload-icon {
            font-size: 28px; /* Slightly larger icon */
            color: #666;
            margin-bottom: 5px;
        }

        /* Adjust text size */
        .upload-area p {
            font-size: 12px; /* Standard text size */
            color: #666;
            margin: 0;
        }

        /* Style the placeholder to be 100x100px */
        .upload-placeholder {
            width: 100px; /* Set size */
            height: 100px; /* Set size */
            display: flex; /* Use flex to center content inside placeholder */
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f0f0f0; /* Light grey background */
            border: 1px solid #ccc; /* Subtle border */
            border-radius: 8px; /* Match container border radius */
            z-index: 1;
            box-sizing: border-box;
            padding: 10px; /* Add padding inside the placeholder */
            flex-shrink: 0; /* Prevent shrinking */
            flex-grow: 0; /* Prevent growing */
             /* Add transition for animation */
            transition: transform 0.3s ease-in-out, border-color 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }

        /* Styles for when the placeholder is disabled/hidden */
        .upload-placeholder.hidden {
            opacity: 0.5; /* Dim the placeholder */
            pointer-events: none; /* Disable clicks */
        }

         /* Make individual preview containers 100x100px */
        .preview-image-container {
            position: relative;
             width: 100px; /* Set size */
             height: 100px; /* Set size */
             border-radius: 8px;
             overflow: hidden;
             box-shadow: 0 2px 4px rgba(0,0,0,0.1);
             flex-shrink: 0;
             flex-grow: 0;
             /* No margin needed here, gap is handled by parent flexbox */
             /* margin: 5px; */
        }

        .preview-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .remove-preview {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255, 68, 68, 0.9);
            color: white;
            width: 20px; /* Smaller remove button */
            height: 20px; /* Smaller remove button */
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px; /* Smaller font size */
            transition: background-color 0.2s;
            z-index: 2;
        }

        .remove-preview:hover {
            background: #ff4444;
        }

        .primary-button {
            background-color: #007bff;
            color: white;
        }

        .primary-button:hover {
            background-color: #0056b3;
        }

        /* Add or update these styles in your existing style section */
        .order-details-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            padding-top: 60px;
        }

        .order-details-content {
            background-color: #ffffff;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
            position: relative;
            margin: 20px auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .order-details-content::-webkit-scrollbar {
            width: 8px;
        }

        .order-details-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .order-details-content::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .order-details-content::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .success-button {
            background-color: #28a745;
            color: white;
        }

        .success-button:hover {
            background-color: #218838;
        }

        .confirm-header {
            background-color: #d4edda;
            color: #155724;
        }

        .confirm-icon {
            color: #28a745;
        }

        /* Update Review Modal Styles */
        #reviewModal {
            display: none;
            position: fixed;
            z-index: 9999; /* Increased z-index to appear above top navigation */
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5); /* Semi-transparent overlay */
            backdrop-filter: blur(2px); /* Optional: adds blur effect to background */
            justify-content: center;
            align-items: center;
        }

        #reviewModal .modal-content {
            background-color: #ffffff;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh; /* Limit height to 90% of viewport */
            overflow-y: auto;
            position: relative;
            margin: 20px auto;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            animation: modalSlideIn 0.3s ease-out;
        }

        /* Add smooth scrollbar for the modal content */
        #reviewModal .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        #reviewModal .modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        #reviewModal .modal-content::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        #reviewModal .modal-content::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>

</head>

<body class="profile-body">
    <div class="dashboard-container">
        <div class="center">
            <?php include 'sidebar.php'; ?>

            <div class="dashboard-content">
                <h2>My Orders</h2>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                     <div class="notification">
                         <div class="notification-content">
                             <i class="fas fa-check-circle"></i>
                             <span><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
                         </div>
                     </div>
    <script>
                         // Remove notification and overlay after animation
                         setTimeout(() => {
                             const notification = document.querySelector('.notification');
                             if (notification) {
                                 notification.style.animation = 'fadeOut 0.3s ease-out';
                                 setTimeout(() => {
                                     notification.remove();
                                 }, 300);
                             }
                         }, 1500);
                     </script>
                 <?php endif; ?>
                 
                 <?php if (isset($_SESSION['error_message'])): ?>
                     <div class="notification error">
                         <div class="notification-content">
                             <i class="fas fa-exclamation-circle"></i>
                             <span><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
                         </div>
                     </div>
                     <script>
                         // Remove notification and overlay after animation
                         setTimeout(() => {
                             const notification = document.querySelector('.notification');
                             if (notification) {
                                 notification.style.animation = 'fadeOut 0.3s ease-out';
                                 setTimeout(() => {
                                     notification.remove();
                                 }, 300);
                             }
                         }, 1500);
                     </script>
                 <?php endif; ?>
                 
                <!-- Order Tabs -->
                <div class="order-tabs">
                    <a href="?tab=to_ship" class="order-tab <?= $currentTab === 'to_ship' ? 'active' : '' ?>">
                        To Ship
                    </a>
                    <a href="?tab=to_receive" class="order-tab <?= $currentTab === 'to_receive' ? 'active' : '' ?>">
                        To Receive
                    </a>
                    <a href="?tab=completed" class="order-tab <?= $currentTab === 'completed' ? 'active' : '' ?>">
                        Completed
                    </a>
                </div>

                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Total</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <?= $currentTab === 'to_receive' ? '<th>Action</th>' : '' ?>
                                    <?= $currentTab === 'completed' ? '<th>Review</th>' : '' ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): 
                                    // Calculate days remaining for delivery if delivery date exists
                                    $deliveryInfo = 'Not available';
                                    if ($row['Delivery_date']) {
                                        $deliveryDate = new DateTime($row['Delivery_date']);
                                        $today = new DateTime();
                                        $interval = $today->diff($deliveryDate);
                                        $daysRemaining = $interval->format('%r%a');
                                        
                                        if ($daysRemaining > 0) {
                                            $deliveryInfo = date('M d, Y', strtotime($row['Delivery_date'])) . 
                                                           ' <span class="delivery-countdown">(' . $daysRemaining . ' day' . 
                                                           ($daysRemaining != 1 ? 's' : '') . ' remaining)</span>';
                                        } else {
                                            $deliveryInfo = date('M d, Y', strtotime($row['Delivery_date']));
                                        }
                                    }
                                ?>
                                    <tr>
                                        <td class="product-cell">
                                            <img src="uploads/<?= htmlspecialchars($row['product_image']) ?>"
                                                alt="<?= htmlspecialchars($row['product_name']) ?>"
                                                class="product-image">
                                            <span class="product-name-link" 
                                                  onclick="showOrderDetails(
                                                      '<?= htmlspecialchars($row['product_name']) ?>',
                                                      '<?= $row['OrderID'] ?>',
                                                      '<?= date('M d, Y', strtotime($row['Paid_time'])) ?>',
                                                      '<?= $row['Quantity'] ?>',
                                                      'RM<?= number_format($row['Subtotal'], 2) ?>',
                                                      'RM<?= number_format($row['TotalPrice'], 2) ?>',
                                                      '<?= $row['ShipmentStatus'] ?>',
                                                      '<?= $row['TrackingNumber'] ?? 'Not available' ?>',
                                                      '<?= $row['Delivery_date'] ? date('M d, Y', strtotime($row['Delivery_date'])) : 'Not available' ?>',
                                                      '<?= $row['Delivery_date'] ? $daysRemaining : '' ?>',
                                                      'uploads/<?= htmlspecialchars($row['product_image']) ?>',
                                                      '<?= $row['DeliveryType'] ?? 'Not specified' ?>'
                                                  )">
                                                <?= htmlspecialchars($row['product_name']) ?>
                                            </span>
                                        </td>
                                        <td><?= $row['Quantity'] ?></td>
                                        <td>RM<?= number_format($row['Subtotal'], 2) ?></td>
                                        <td>RM<?= number_format($row['TotalPrice'], 2) ?></td>
                                        <td><?= date('M d, Y', strtotime($row['Paid_time'])) ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = strtolower(str_replace(' ', '-', $row['ShipmentStatus']));
                                            echo "<span class='status-badge status-{$statusClass}'>{$row['ShipmentStatus']}</span>";
                                            ?>
                                        </td>
                                        
                                        <?php if ($currentTab === 'to_receive'): ?>
                                            <td>
                                                <form method="POST" id="receiveForm_<?= $row['OrderID'] ?>" onsubmit="return confirmReceive(event, <?= $row['OrderID'] ?>)">
                                                    <input type="hidden" name="order_id" value="<?= $row['OrderID'] ?>">
                                                    <button type="submit" class="button">Receive</button>
                                                </form>
                                            </td>
                                        <?php elseif ($currentTab === 'completed'): ?>
                                            <td>
                                                <?php 
                                                // Check if review exists
                                                $reviewCheck = $conn->prepare("SELECT ReviewID FROM reviews WHERE OrderID = ? AND ProductID = ? AND CustomerID = ?");
                                                $reviewCheck->bind_param("iii", $row['OrderID'], $row['product_id'], $customerID);
                                                $reviewCheck->execute();
                                                $hasReview = $reviewCheck->get_result()->num_rows > 0;
                                                $reviewCheck->close();
                                                
                                                if (!$hasReview): ?>
                                                    <button onclick="openReviewModal(<?= $row['OrderID'] ?>, <?= $row['product_id'] ?>)" 
                                                            class="button">
                                                        Rate
                                                    </button>
                                                <?php else: ?>
                                                    <a href="product_details.php?product_id=<?= $row['product_id'] ?>" class="button">Buy Again</a>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-orders">
                        <p>No orders found in this category.</p>
                        <a href="home.php" class="button">Browse Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div id="orderDetailsModal" class="order-details-modal">
        <div class="order-details-content">
            <span class="close-modal" onclick="closeOrderDetailsModal()">&times;</span>
            <div class="order-details-title">Order Details</div>
            
            <div style="display: flex; align-items: center; margin-bottom: 20px;">
                <img id="orderDetailsImage" src="" class="order-details-image">
                <div>
                    <div style="font-weight: bold; font-size: 18px;" id="orderDetailsProductName"></div>
                    <div style="color: #666;" id="orderDetailsOrderID"></div>
                </div>
            </div>
            
            <div class="order-details-row">
                <div class="order-details-label">Order Date:</div>
                <div class="order-details-value" id="orderDetailsDate"></div>
            </div>
            
            <div class="order-details-row">
                <div class="order-details-label">Quantity:</div>
                <div class="order-details-value" id="orderDetailsQuantity"></div>
            </div>
            
            <div class="order-details-row">
                <div class="order-details-label">Subtotal:</div>
                <div class="order-details-value" id="orderDetailsSubtotal"></div>
            </div>
            
            <div class="order-details-row">
                <div class="order-details-label">Total Price:</div>
                <div class="order-details-value" id="orderDetailsTotal"></div>
            </div>
            
            <div class="order-details-row">
                <div class="order-details-label">Status:</div>
                <div class="order-details-value">
                    <span id="orderDetailsStatus"></span>
                </div>
            </div>
            
            <div class="order-details-row">
                <div class="order-details-label">Tracking Number:</div>
                <div class="order-details-value" id="orderDetailsTracking"></div>
            </div>
            
            <div class="order-details-row">
                <div class="order-details-label">Estimated Delivery:</div>
                <div class="order-details-value" id="orderDetailsDelivery"></div>
            </div>

            <div class="order-details-row">
                <div class="order-details-label">Delivery Type:</div>
                <div class="order-details-value" id="orderDetailsDeliveryType"></div>
            </div>

            <!-- Add Cancel Order Button -->
            <div id="cancelOrderSection" style="display: none; margin-top: 20px; text-align: center;">
                <button onclick="showCancelWarning()" class="button" style="background-color: #dc3545;">Cancel Order</button>
            </div>
        </div>
    </div>

    <!-- Cancel Warning Modal -->
    <div id="cancelWarningModal" class="modal">
        <div class="modal-content warning-modal">
            <div class="modal-header warning-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Cancel Order Warning</h3>
                <span class="close-modal" onclick="closeCancelWarning()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="warning-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="warning-message">
                    <p>Please note that orders can only be cancelled if they are in "Preparing" status. Once the order is shipped, it cannot be cancelled.</p>
                </div>
                <div class="warning-checkbox">
                    <input type="checkbox" id="dontShowAgain">
                    <label for="dontShowAgain">Don't show this message again</label>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeCancelWarning()" class="button secondary-button">
                    <i class="fas fa-times"></i> Close
                </button>
                <button onclick="showCancelConfirmation()" class="button warning-button">
                    <i class="fas fa-arrow-right"></i> Continue
                </button>
            </div>
        </div>
    </div>

    <!-- Cancel Confirmation Modal -->
    <div id="cancelConfirmationModal" class="modal">
        <div class="modal-content confirm-modal">
            <div class="modal-header confirm-header">
                <i class="fas fa-question-circle"></i>
                <h3>Confirm Order Cancellation</h3>
                <span class="close-modal" onclick="closeCancelConfirmation()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="confirm-icon">
                    <i class="fas fa-hand-paper"></i>
                </div>
                <div class="confirm-message">
                    <p>Are you sure you want to cancel this order?</p>
                    <p class="warning-text">This action cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeCancelConfirmation()" class="button secondary-button">
                    <i class="fas fa-times"></i> No, Keep Order
                </button>
                <button onclick="submitCancelOrder()" class="button danger-button">
                    <i class="fas fa-ban"></i> Yes, Cancel Order
                </button>
            </div>
        </div>
    </div>

    <!-- Review Modal -->
    <div id="reviewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Write a Review</h3>
                <span class="close-modal" onclick="closeReviewModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="reviewForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="review_submit" value="1">
                    <input type="hidden" id="review_order_id" name="order_id">
                    <input type="hidden" id="review_product_id" name="product_id">
                    
                    <div class="image-upload">
                        <label>Add Photos (Optional):</label>
                        <div class="upload-area" onclick="triggerFileInput()">
                            <input type="file" id="mainFileInput" name="review_images[]" multiple accept="image/*" style="display: none;" onchange="handleImageSelection(event)">
                            <div class="upload-placeholder">
                                <div class="upload-icon">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <p>Click to upload images</p>
                            </div>
                        </div>
                    </div>

                    <div class="rating-input">
                        <label>Rating:</label>
                        <div class="star-rating">
                            <input type="radio" id="star5" name="rating" value="5">
                            <label for="star5">★</label>
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4">★</label>
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3">★</label>
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2">★</label>
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1">★</label>
                        </div>
                    </div>

                    <div class="review-text">
                        <label for="review_text">Your Review:</label>
                        <textarea id="review_text" name="review_text" rows="4" required></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="button secondary-button" onclick="closeReviewModal()">Cancel</button>
                        <button type="submit" class="button primary-button">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Review Success Notification -->
    <div id="reviewSuccessNotification" class="notification" style="display: none;">
        <div class="notification-content">
            <i class="fas fa-check-circle"></i>
            <span>Thank you for your review!</span>
        </div>
    </div>

    <!-- Receive Confirmation Modal -->
    <div id="receiveConfirmationModal" class="modal">
        <div class="modal-content confirm-modal">
            <div class="modal-header confirm-header">
                <i class="fas fa-check-circle"></i>
                <h3>Confirm Order Receipt</h3>
                <span class="close-modal" onclick="closeReceiveConfirmation()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="confirm-icon">
                    <i class="fas fa-box-open"></i>
                </div>
                <div class="confirm-message">
                    <p>Are you sure you have received this order?</p>
                </div>
            </div>
            <div class="modal-footer">
                <button onclick="closeReceiveConfirmation()" class="button secondary-button">
                    <i class="fas fa-times"></i> No, Cancel
                </button>
                <button onclick="submitReceiveOrder()" class="button success-button">
                    <i class="fas fa-check"></i> Yes, Confirm Receipt
                </button>
            </div>
        </div>
    </div>

    <script>
        // Global array to store selected files
        let selectedReviewFiles = [];
        const MAX_REVIEW_IMAGES = 3; // Define max image limit

        // Order Details Modal Functions
        let currentOrderId = null;
        let currentOrderStatus = null;

        function showOrderDetails(productName, orderID, orderDate, quantity, subtotal, 
                                total, status, tracking, deliveryDate, daysRemaining, productImage, deliveryType) {
            const modal = document.getElementById('orderDetailsModal');
            currentOrderId = orderID;
            currentOrderStatus = status;
            
            // Set all the values in the modal
            document.getElementById('orderDetailsProductName').textContent = productName;
            document.getElementById('orderDetailsOrderID').textContent = 'Order #' + orderID;
            document.getElementById('orderDetailsImage').src = productImage;
            document.getElementById('orderDetailsDate').textContent = orderDate;
            document.getElementById('orderDetailsQuantity').textContent = quantity;
            document.getElementById('orderDetailsSubtotal').textContent = subtotal;
            document.getElementById('orderDetailsTotal').textContent = total;
            document.getElementById('orderDetailsDeliveryType').textContent = deliveryType;
            
            // Set status with appropriate badge
            const statusElement = document.getElementById('orderDetailsStatus');
            statusElement.innerHTML = `<span class="status-badge status-${status.toLowerCase()}">${status}</span>`;
            
            // Display tracking number or "Not available"
            document.getElementById('orderDetailsTracking').textContent = tracking || 'Not available';
            
            // Format and display delivery date
            let deliveryText = 'Not available';
            if (deliveryDate && deliveryDate !== 'Not available') {
                deliveryText = deliveryDate;
                if (daysRemaining > 0) {
                    deliveryText += ` <span class="delivery-countdown">(${daysRemaining} day${daysRemaining != 1 ? 's' : ''} remaining)</span>`;
                }
            }
            document.getElementById('orderDetailsDelivery').innerHTML = deliveryText;

            // Show/hide cancel button based on status
            const cancelSection = document.getElementById('cancelOrderSection');
            if (status === 'Preparing') {
                cancelSection.style.display = 'block';
            } else {
                cancelSection.style.display = 'none';
            }
            
            // Show the modal
            modal.style.display = 'flex';
        }
        
        function closeOrderDetailsModal() {
            document.getElementById('orderDetailsModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const orderModal = document.getElementById('orderDetailsModal');
            const reviewModal = document.getElementById('reviewModal');
            
            if (event.target === orderModal) {
                closeOrderDetailsModal();
            }
            if (event.target === reviewModal) {
                closeReviewModal();
            }
        }
        
        // Close modal with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeOrderDetailsModal();
                closeReviewModal();
            }
        });
        
        // Review Modal Functions
        function openReviewModal(orderId, productId) {
            document.getElementById('review_order_id').value = orderId;
            document.getElementById('review_product_id').value = productId;
            document.getElementById('reviewModal').style.display = 'flex';
            
            // Reset selected files and clear previews when opening
            selectedReviewFiles = [];
            renderReviewPreviews(); // Render to clear existing previews

            document.getElementById('mainFileInput').value = ''; // Clear file input value
            document.getElementById('reviewForm').reset(); // Reset other form fields
             updatePlaceholderVisibility(); // Ensure placeholder is visible
        }
        
        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';

            // Reset selected files and clear previews when closing
            selectedReviewFiles = [];
            renderReviewPreviews(); // Render to clear previews

            document.getElementById('mainFileInput').value = ''; // Clear file input value
            document.getElementById('reviewForm').reset(); // Reset form
             updatePlaceholderVisibility(); // Ensure placeholder is visible
        }
        
        function triggerFileInput() {
            // Trigger the click on the hidden file input
            document.getElementById('mainFileInput').click();
        }
        
        function handleImageSelection(event) {
            const files = event.target.files; // Get files from the input change event
            const filesArray = Array.from(files);
            
            // Add new files to our selected files array, respecting the max limit
            filesArray.forEach(file => {
                // Prevent adding more than MAX_REVIEW_IMAGES total
                if (selectedReviewFiles.length < MAX_REVIEW_IMAGES) {
                     // Optional: Prevent adding duplicate files (basic check by name and size)
                     const isDuplicate = selectedReviewFiles.some(existingFile =>
                         existingFile.name === file.name && existingFile.size === file.size
                     );
                     if (!isDuplicate) {
                        selectedReviewFiles.push(file);
                     }
                }
            });
                
            // Clear the file input value to allow selecting the same file(s) again later if needed
            event.target.value = '';

            // Render the updated list of selected files
            renderReviewPreviews();
        }

        // Function to render the image previews based on the selectedReviewFiles array
        function renderReviewPreviews() {
            const uploadArea = document.querySelector('.upload-area');
            const placeholder = document.querySelector('.upload-placeholder');

            // Remove all existing previews (they will be re-rendered from selectedReviewFiles)
            uploadArea.querySelectorAll('.preview-image-container').forEach(preview => preview.remove());
                
            // Render a preview for each file in the selectedReviewFiles array
            selectedReviewFiles.forEach((file, index) => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const previewDiv = document.createElement('div');
                        previewDiv.className = 'preview-image-container';
                        previewDiv.innerHTML = `
                            <img src="${e.target.result}" class="preview-image">
                        <span class="remove-preview" data-index="${index}">&times;</span>
                    `;
                    // Insert the new preview before the placeholder
                    uploadArea.insertBefore(previewDiv, placeholder);

                    // Add event listener to the remove button
                    previewDiv.querySelector('.remove-preview').addEventListener('click', function() {
                        // Get the index from the data attribute
                        const indexToRemove = parseInt(this.getAttribute('data-index'), 10);
                        // Remove the file from the array
                        selectedReviewFiles.splice(indexToRemove, 1);
                        // Re-render all previews (simplest way to update indices and display)
                        renderReviewPreviews();
                    });
                };
                    reader.readAsDataURL(file);
                });

            // Update placeholder visibility based on the number of selected files
            updatePlaceholderVisibility();
        }

        function updatePlaceholderVisibility() {
            const placeholder = document.querySelector('.upload-placeholder');
            // Use selectedReviewFiles array length
            if (selectedReviewFiles.length >= MAX_REVIEW_IMAGES) {
                placeholder.classList.add('hidden');
            } else {
                placeholder.classList.remove('hidden');
            }
        }
        
        // Form validation and submission using Fetch API and FormData
        document.getElementById('reviewForm').addEventListener('submit', async function(e) {
            e.preventDefault(); // Prevent default form submission

            const rating = document.querySelector('input[name="rating"]:checked');
            const reviewText = document.getElementById('review_text').value.trim();
            
            if (!rating) {
                alert('Please select a rating');
                return false;
            }
            
            if (!reviewText) {
                alert('Please write your review');
                return false;
            }
            
            const formData = new FormData(this);

            // Append each selected file to the FormData object
            selectedReviewFiles.forEach((file) => {
                formData.append('review_images[]', file);
            });

            try {
                const response = await fetch(this.action, {
                    method: this.method,
                    body: formData
                });

                // Check if the response is a redirect
                if (response.redirected) {
                    // Show success notification before redirecting
                    const notification = document.getElementById('reviewSuccessNotification');
                    notification.style.display = 'block';
                    
                    // Wait for 1.5 seconds to show the notification
                    await new Promise(resolve => setTimeout(resolve, 1500));

                    // Redirect after showing notification
                    window.location.href = response.url;
                } else {
                    // If not redirected, parse the response
                    const text = await response.text();
                    console.log('Server Response:', text);
                    // Handle non-redirect responses if needed
                }
            } catch (error) {
                console.error('Fetch Error:', error);
                alert('An error occurred during submission.');
            }
        });

        // Add these new functions for cancel order functionality
        function showCancelWarning() {
            // Check if user has chosen not to show the warning
            if (localStorage.getItem('dontShowCancelWarning') === 'true') {
                showCancelConfirmation();
                return;
            }
            document.getElementById('cancelWarningModal').style.display = 'flex';
        }

        function closeCancelWarning() {
            document.getElementById('cancelWarningModal').style.display = 'none';
            // Save user preference
            if (document.getElementById('dontShowAgain').checked) {
                localStorage.setItem('dontShowCancelWarning', 'true');
            }
        }

        function showCancelConfirmation() {
            closeCancelWarning();
            document.getElementById('cancelConfirmationModal').style.display = 'flex';
        }

        function closeCancelConfirmation() {
            document.getElementById('cancelConfirmationModal').style.display = 'none';
        }

        function submitCancelOrder() {
            // Create and submit the form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'order.php';

            const orderIdInput = document.createElement('input');
            orderIdInput.type = 'hidden';
            orderIdInput.name = 'order_id';
            orderIdInput.value = currentOrderId;

            const statusInput = document.createElement('input');
            statusInput.type = 'hidden';
            statusInput.name = 'new_status';
            statusInput.value = 'Cancelled';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'cancel_action';
            actionInput.value = 'true';

            const currentTabInput = document.createElement('input');
            currentTabInput.type = 'hidden';
            currentTabInput.name = 'current_tab';
            currentTabInput.value = '<?php echo $currentTab; ?>';

            form.appendChild(orderIdInput);
            form.appendChild(statusInput);
            form.appendChild(actionInput);
            form.appendChild(currentTabInput);
            document.body.appendChild(form);
            form.submit();
        }

        // Add these functions to your existing JavaScript
        let currentReceiveForm = null;

        function confirmReceive(event, orderId) {
            event.preventDefault();
            currentReceiveForm = document.getElementById('receiveForm_' + orderId);
            document.getElementById('receiveConfirmationModal').style.display = 'flex';
        }

        function closeReceiveConfirmation() {
            document.getElementById('receiveConfirmationModal').style.display = 'none';
            currentReceiveForm = null;
        }

        function submitReceiveOrder() {
            if (currentReceiveForm) {
                currentReceiveForm.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const receiveModal = document.getElementById('receiveConfirmationModal');
            if (event.target === receiveModal) {
                closeReceiveConfirmation();
            }
        }

        // Close modal with ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeReceiveConfirmation();
            }
        });

        // Add hover effect to placeholder after it's in the DOM (handled by CSS now)
        /*
         const placeholderElement = document.querySelector('.upload-placeholder');
         if(placeholderElement) {
             placeholderElement.addEventListener('mouseover', function() { this.style.transform = 'scale(1.05)'; this.style.borderColor = '#007bff'; });
             placeholderElement.addEventListener('mouseout', function() { this.style.transform = 'scale(1)'; this.style.borderColor = '#ccc'; }); // Revert to original border color
         }
        */
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>