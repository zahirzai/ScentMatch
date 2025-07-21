<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_name'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Check if feedback ID is provided
if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'No feedback ID provided']);
    exit();
}

$feedback_id = intval($_POST['id']);
$customer_id = $_SESSION['customerID'];

// Connect to database
$conn = new mysqli("localhost", "root", "", "scentmatch3");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Verify that the feedback belongs to the current user and delete it
$stmt = $conn->prepare("DELETE FROM feedback WHERE feedback_id = ? AND customer_id = ?");
$stmt->bind_param("ii", $feedback_id, $customer_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to delete feedback']);
}

$stmt->close();
$conn->close();
?> 