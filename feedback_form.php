<?php
// feedback_form.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$errors = [];
$success = '';
$customerId = $_SESSION['customerID'] ?? null;

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $feedback = htmlspecialchars(trim($_POST['feedback'] ?? ''));
    $rating = (int) ($_POST['rating'] ?? 0);

    // Validation
    if (empty($feedback)) {
        $errors[] = "Feedback message is required.";
    }

    if ($rating < 1 || $rating > 5) {
        $errors[] = "Please select a valid rating (1-5).";
    }

    if (empty($errors)) {
        $conn = new mysqli("localhost", "root", "", "scentmatch3");

        if ($conn->connect_error) {
            $errors[] = "Database connection failed.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO feedback (customer_id, subject, message, rating, status, feedback_source)
                VALUES (?, ?, ?, ?, 'new', 'customer')
            ");
            $stmt->bind_param("issi", $customerId, $subject, $feedback, $rating);

            if ($stmt->execute()) {
                $success = "Thank you for your feedback!";
                // Reset form values
                $subject = '';
                $feedback = '';
                $rating = 0;
            } else {
                $errors[] = "Error submitting feedback. Please try again later.";
            }

            $stmt->close();
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Form</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .feedback-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background-color: #16a085;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .feedback-button:hover {
            transform: scale(1.1);
            background-color: #138d76;
        }

        .feedback-button i {
            font-size: 24px;
        }

        .feedback-modal {
            display: none;
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 400px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            z-index: 1001;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .close-btn {
            position: absolute;
            right: 15px;
            top: 15px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            transition: color 0.2s;
        }

        .close-btn:hover {
            color: #333;
        }

        .feedback-form {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-group input[type="text"]:focus,
        .form-group textarea:focus {
            border-color: #16a085;
            outline: none;
        }

        .rating-stars {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 5px;
        }

        .rating-stars input {
            display: none;
        }

        .rating-stars label {
            font-size: 24px;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s;
        }

        .rating-stars label:hover,
        .rating-stars label:hover ~ label,
        .rating-stars input:checked ~ label {
            color: #ffc107;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #16a085;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .submit-btn:hover {
            background-color: #138d76;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            animation: fadeOut 3s forwards 3s;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
        }

        @keyframes fadeOut {
            to { opacity: 0; height: 0; padding: 0; margin: 0; }
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <!-- Floating Feedback Button -->
    <div class="feedback-button" id="feedbackButton">
        <i class="fas fa-comment-dots"></i>
    </div>

    <!-- Feedback Modal -->
    <div class="feedback-modal" id="feedbackModal">
        <div class="modal-header">
            <h3>We'd love your feedback!</h3>
            <button class="close-btn" id="closeFeedback">&times;</button>
        </div>

        <div class="success-message" id="successMessage"></div>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form class="feedback-form" method="POST" id="feedbackForm">
            <div class="form-group">
                <label for="subject">Subject (optional)</label>
                <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>">
            </div>

            <div class="form-group">
                <label>Rating</label>
                <div class="rating-stars">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" <?php echo (isset($rating) && $rating == $i) ? 'checked' : ''; ?>>
                        <label for="star<?php echo $i; ?>">★</label>
                    <?php endfor; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="feedback">Your Feedback*</label>
                <textarea id="feedback" name="feedback" rows="4" required><?php echo htmlspecialchars($feedback ?? ''); ?></textarea>
            </div>

            <button type="submit" class="submit-btn">Submit Feedback</button>
        </form>
    </div>

    <script>
        document.getElementById('feedbackButton').addEventListener('click', function() {
            document.getElementById('feedbackModal').style.display = 'block';
            // Reset form and hide success message when opening modal
            document.getElementById('feedbackForm').reset();
            document.getElementById('successMessage').style.display = 'none';
        });

        document.getElementById('closeFeedback').addEventListener('click', function() {
            document.getElementById('feedbackModal').style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            const modal = document.getElementById('feedbackModal');
            const button = document.getElementById('feedbackButton');
            if (!modal.contains(event.target) && !button.contains(event.target)) {
                modal.style.display = 'none';
            }
        });

        // Handle form submission
        document.getElementById('feedbackForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Submit form via AJAX
            const formData = new FormData(this);
            
            fetch('feedback_form.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                // Show success message
                const successMessage = document.getElementById('successMessage');
                successMessage.textContent = "Thank you for your feedback!";
                successMessage.style.display = 'block';
                
                // Reset form
                this.reset();
                
                // Hide success message after 3 seconds
                setTimeout(() => {
                    successMessage.style.display = 'none';
                }, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
            });
        });

        <?php if (!empty($success)): ?>
        // Show success message if coming from PHP submission
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('successMessage');
            successMessage.textContent = "<?php echo htmlspecialchars($success); ?>";
            successMessage.style.display = 'block';
            
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 3000);
        });
        <?php endif; ?>
    </script>
</body>
</html>