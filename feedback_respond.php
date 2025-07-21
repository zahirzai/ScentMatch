<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'unresolved';

// ================== FETCH FEEDBACK ==================
$statusCondition = $currentTab === 'resolved' ? "f.status = 'resolved'" : "f.status = 'new'";

$sql = "SELECT f.feedback_id, f.message, f.subject, f.status, f.created_at, 
               f.rating, f.admin_response, f.resolved_at,
               c.Username AS customer_name, c.CustomerID
        FROM feedback f
        LEFT JOIN customer c ON f.customer_id = c.CustomerID
        WHERE f.customer_id = ? AND f.feedback_source = 'customer' AND {$statusCondition}
        ORDER BY f.created_at DESC";

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
    <title>My Feedback Responses | ScentMatch</title>
    <link rel="stylesheet" href="css/top-navigation.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Order Tabs Styling */
        .order-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
            width: 100%;
            gap: 0;
        }
        
        .order-tab {
            padding: 12px 24px;
            cursor: pointer;
            text-decoration: none;
            color: #555;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            margin-right: 0;
            position: relative;
            flex-grow: 1;
            text-align: center;
        }
        
        .order-tab:last-child {
            border-right: none;
        }
        
        .order-tab:hover {
            color: #000;
            background-color: #f8f8f8;
            border-bottom-color: #ccc;
        }
        
        .order-tab.active {
            color: #000;
            font-weight: 600;
            border-bottom-color:rgb(0, 0, 0);
            background-color: #f1f1f1;
        }

        .order-tab:not(:last-child)::after {
            content: "";
            position: absolute;
            right: 0;
            top: 25%;
            height: 50%;
            width: 1px;
            background-color: #ddd;
        }
        
        /* Feedback Items Styling */
        .feedback-container {
            margin: 20px 0;
        }
        
        .feedback-item {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .feedback-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            align-items: center;
        }
        
        .feedback-meta {
            display: flex;
            align-items: center;
        }
        
        .feedback-date {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .feedback-rating {
            margin-right: 15px;
            font-size: 1.2em;
            color: #ffc107;
        }
        
        .feedback-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .status-new {
            background-color: #3498db;
            color: white;
        }
        
        .status-in_progress {
            background-color: #f39c12;
            color: white;
        }
        
        .status-resolved {
            background-color: #2ecc71;
            color: white;
        }
        
        .status-rejected {
            background-color: #e74c3c;
            color: white;
        }
        
        .feedback-subject {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .feedback-message {
            margin: 15px 0;
            line-height: 1.6;
        }
        
        .admin-response {
            background-color: #f8f9fa;
            border-left: 4px solid #16a085;
            padding: 15px;
            margin-top: 20px;
            border-radius: 0 4px 4px 0;
        }
        
        .admin-response-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #16a085;
        }
        
        .resolved-date {
            margin-top: 15px;
            font-size: 0.9em;
            color: #6c757d;
            text-align: right;
        }
        
        .no-feedback {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .no-feedback i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #e9ecef;
        }
        
        .feedback-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }
        
        .delete-btn {
            background-color: #000000;
            color: white;
        }
        
        .delete-btn:hover {
            background-color: #333333;
        }
        
        .alert {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Custom Delete Confirmation Popup */
        .delete-popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: flex-start;
            padding-top: 50px;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .delete-popup.show {
            opacity: 1;
        }

        .delete-popup-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            position: relative;
            transform: translateY(-100px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .delete-popup.show .delete-popup-content {
            transform: translateY(0);
            opacity: 1;
        }

        .delete-popup-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: #333;
        }

        .delete-popup-message {
            margin-bottom: 25px;
            color: #666;
        }

        .delete-popup-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .delete-popup-btn {
            padding: 10px 25px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .delete-confirm-btn {
            background: #000;
            color: white;
        }

        .delete-confirm-btn:hover {
            background: #333;
            transform: translateY(-2px);
        }

        .delete-cancel-btn {
            background: #f1f1f1;
            color: #333;
        }

        .delete-cancel-btn:hover {
            background: #e1e1e1;
            transform: translateY(-2px);
        }
    </style>
</head>

<body class="profile-body">
    <div class="dashboard-container">
        <div class="center">
            <?php include 'sidebar.php'; ?>

            <div class="dashboard-content">
                <h2>My Feedback Responses</h2>
                
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger"><?= $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
                <?php endif; ?>
                
                <!-- Feedback Tabs -->
                <div class="order-tabs">
                    <a href="?tab=unresolved" class="order-tab <?= $currentTab === 'unresolved' ? 'active' : '' ?>">
                        Unresolved
                    </a>
                    <a href="?tab=resolved" class="order-tab <?= $currentTab === 'resolved' ? 'active' : '' ?>">
                        Resolved
                    </a>
                </div>

                <div class="feedback-container">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <div class="feedback-item" data-feedback-id="<?= $row['feedback_id'] ?>">
                                <div class="feedback-header">
                                    <div class="feedback-meta">
                                        <?php if (isset($row['rating'])): ?>
                                            <div class="feedback-rating">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <?php if ($i <= $row['rating']): ?>
                                                        <i class="fas fa-star"></i>
                                                    <?php else: ?>
                                                        <i class="far fa-star"></i>
                                                    <?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="feedback-date">
                                            <?= date('M d, Y', strtotime($row['created_at'])) ?>
                                        </div>
                                    </div>
                                    <span class="feedback-status status-<?= $row['status'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $row['status'])) ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($row['subject'])): ?>
                                    <div class="feedback-subject">
                                        <?= htmlspecialchars($row['subject']) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="feedback-message">
                                    <?= nl2br(htmlspecialchars($row['message'])) ?>
                                </div>
                                
                                <?php if (!empty($row['admin_response'])): ?>
                                    <div class="admin-response">
                                        <div class="admin-response-title">Admin Response:</div>
                                        <?= nl2br(htmlspecialchars($row['admin_response'])) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($row['status'] === 'resolved' && !empty($row['resolved_at'])): ?>
                                    <div class="resolved-date">
                                        Resolved on <?= date('M d, Y', strtotime($row['resolved_at'])) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="feedback-actions">
                                    <?php if ($row['status'] !== 'resolved'): ?>
                                        <button class="action-btn delete-btn" onclick="showDeletePopup(<?= $row['feedback_id'] ?>)">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-feedback">
                            <i class="far fa-comment-dots"></i>
                            <h3>No <?= $currentTab ?> feedback found</h3>
                            <p>You don't have any <?= $currentTab ?> feedback at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Popup -->
    <div class="delete-popup" id="deletePopup">
        <div class="delete-popup-content">
            <h3 class="delete-popup-title">Delete Feedback</h3>
            <p class="delete-popup-message">Are you sure you want to delete this feedback? This action cannot be undone.</p>
            <div class="delete-popup-buttons">
                <button class="delete-popup-btn delete-cancel-btn" onclick="closeDeletePopup()">Cancel</button>
                <button class="delete-popup-btn delete-confirm-btn" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>

    <script>
        let feedbackToDelete = null;

        function showDeletePopup(feedbackId) {
            feedbackToDelete = feedbackId;
            const popup = document.getElementById('deletePopup');
            popup.style.display = 'flex';
            // Trigger reflow
            popup.offsetHeight;
            popup.classList.add('show');
        }

        function closeDeletePopup() {
            const popup = document.getElementById('deletePopup');
            popup.classList.remove('show');
            // Wait for animation to complete before hiding
            setTimeout(() => {
                popup.style.display = 'none';
            }, 300);
            feedbackToDelete = null;
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (feedbackToDelete) {
                // Send AJAX request to delete the feedback
                fetch('delete_feedback.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + feedbackToDelete
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the feedback item from the DOM
                        const feedbackItem = document.querySelector(`[data-feedback-id="${feedbackToDelete}"]`);
                        if (feedbackItem) {
                            feedbackItem.style.transition = 'all 0.3s ease-in-out';
                            feedbackItem.style.opacity = '0';
                            feedbackItem.style.transform = 'translateY(-20px)';
                            setTimeout(() => {
                                feedbackItem.remove();
                            }, 300);
                        }
                        closeDeletePopup();
                    } else {
                        alert('Error deleting feedback. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting feedback. Please try again.');
                });
            }
        });
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>

<?php 
$stmt->close();
$conn->close();
?>