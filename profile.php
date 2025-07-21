<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['customerID'])) {
    header("Location: auth.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "scentmatch3");
include 'top-navigation.php';

$customerID = $_SESSION['customerID'];

// Fetch user information
$user = [];
$stmt = $conn->prepare("SELECT Name, Username, DOB, Phone, Email, Address FROM customer WHERE CustomerID = ?");
$stmt->bind_param("i", $customerID);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    die("Error: User not found");
}

// Fetch preferences
$preferences = [];
$preferencesQuery = $conn->prepare("
    SELECT 
        q.gender,
        q.Personality,
        l.Description AS lifestyle,
        c.ConcentrationName AS concentration,
        GROUP_CONCAT(DISTINCT s.ScentName SEPARATOR ', ') AS scents
    FROM 
        preference p
    JOIN 
        question q ON p.questionID = q.questionID
    LEFT JOIN 
        question_scent qs ON q.questionID = qs.questionID
    LEFT JOIN 
        scents s ON qs.ScentID = s.ScentID
    LEFT JOIN 
        concentration c ON q.ConcentrationID = c.ConcentrationID
    LEFT JOIN
        lifestyle l ON q.LifestyleID = l.LifestyleID
    WHERE 
        p.customerID = ?
    GROUP BY 
        q.questionID
    ORDER BY
        p.preferenceID DESC
    LIMIT 1
");
$preferencesQuery->bind_param("i", $customerID);
$preferencesQuery->execute();
$preferencesResult = $preferencesQuery->get_result();

if ($preferencesResult->num_rows > 0) {
    $preferences = $preferencesResult->fetch_assoc();
}

// Personality icons mapping
$personalityIcons = [
    'bold' => '🔥',
    'calm' => '🌿',
    'elegant' => '💫',
    'mysterious' => '🎭',
    'playful' => '🌞'
];

// Split personalities if multiple exist
$personalities = isset($preferences['Personality']) ? explode(',', $preferences['Personality']) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="css/top-navigation.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .preference-section {
            margin-top: 20px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
        .preference-item {
            margin-bottom: 15px;
        }
        .personality-tag {
            display: inline-block;
            background-color: #f0f0f0;
            padding: 5px 10px;
            border-radius: 15px;
            margin-right: 8px;
            margin-bottom: 8px;
        }
        .scent-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .scent-tag {
            background-color: #e8f4fc;
            padding: 5px 12px;
            border-radius: 15px;
        }
        .setup-link {
            color: #000;
            text-decoration: none;
            font-size: 13px;
            margin-left: 8px;
            transition: all 0.3s ease;
            opacity: 0.7;
        }
        .setup-link:hover {
            opacity: 1;
        }
        .setup-link i {
            margin-left: 4px;
        }
        .pref-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #000;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .footer {
            background-color: #1a1a1a;
            color: #fff;
            padding: 40px 0 20px;
            margin-top: 50px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
        }

        .footer-section h3 {
            color: #fff;
            margin-bottom: 20px;
            font-size: 18px;
        }

        .footer-section p {
            color: #ccc;
            line-height: 1.6;
            margin: 0;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 10px;
        }

        .footer-section ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: #fff;
        }

        .footer-section ul li i {
            margin-right: 10px;
            color: #fff;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #333;
        }

        .footer-bottom p {
            color: #ccc;
            margin: 0;
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .footer-section {
                text-align: center;
            }

            .footer-section ul li i {
                margin-right: 5px;
            }
        }
    </style>
</head>

<body class="profile-body">
    <div class="dashboard-container">
        <div class="center">
            <?php include 'sidebar.php'; ?>

            <div class="dashboard-content">
                <div class="info">
                    <h2>Profile Information</h2>
                    <p>
                        <strong>Full Name:</strong>
                        <?php echo !empty($user['Name']) ? htmlspecialchars($user['Name']) : ''; ?>
                        <?php if (empty($user['Name'])): ?>
                            <a href="account_details.php" class="setup-link">Set up <i class="fas fa-arrow-right"></i></a>
                        <?php endif; ?>
                    </p>

                    <p>
                        <strong>Date of Birth:</strong>
                        <?php echo !empty($user['DOB']) ? htmlspecialchars($user['DOB']) : ''; ?>
                        <?php if (empty($user['DOB'])): ?>
                            <a href="account_details.php" class="setup-link">Set up <i class="fas fa-arrow-right"></i></a>
                        <?php endif; ?>
                    </p>

                    <p>
                        <strong>Phone:</strong>
                        <?php echo !empty($user['Phone']) ? htmlspecialchars($user['Phone']) : ''; ?>
                        <?php if (empty($user['Phone'])): ?>
                            <a href="account_details.php" class="setup-link">Set up <i class="fas fa-arrow-right"></i></a>
                        <?php endif; ?>
                    </p>

                    <p>
                        <strong>Email:</strong>
                        <?php echo htmlspecialchars($user['Email']); ?>
                    </p>

                    <p>
                        <strong>Address:</strong>
                        <?php echo !empty($user['Address']) ? htmlspecialchars($user['Address']) : ''; ?>
                        <?php if (empty($user['Address'])): ?>
                            <a href="address.php" class="setup-link">Set up <i class="fas fa-arrow-right"></i></a>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="preferences">
                    <h3>Your Preferences</h3>
                    <?php if (!empty($preferences)): ?>
                        <div class="preference-section">
                            <div class="preference-item">
                                <strong>Gender:</strong>
                                <span><?php echo ucfirst(htmlspecialchars($preferences['gender'])); ?></span>
                            </div>

                            <div class="preference-item">
                                <strong>Lifestyle:</strong>
                                <span><?php echo htmlspecialchars($preferences['lifestyle']); ?></span>
                            </div>

                            <div class="preference-item">
                                <strong>Preferred Scents:</strong>
                                <div class="scent-list">
                                    <?php foreach (explode(', ', $preferences['scents']) as $scent): ?>
                                        <span class="scent-tag"><?php echo htmlspecialchars($scent); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="preference-item">
                                <strong>Personality:</strong>
                                <div>
                                    <?php foreach ($personalities as $personality): ?>
                                        <?php if (isset($personalityIcons[$personality])): ?>
                                            <span class="personality-tag">
                                                <?php echo $personalityIcons[$personality] . ' ' . ucfirst($personality); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="preference-item">
                                <strong>Preferred Concentration:</strong>
                                <span><?php echo htmlspecialchars($preferences['concentration']); ?></span>
                            </div>
                        </div>
                        <a href="question.php?fromProfile=true" class="pref-button">Update Preferences</a>
                    <?php else: ?>
                        <p>You haven't set up your scent preferences yet.</p>
                        <a href="question.php?fromProfile=true" class="pref-button">Set Up Preferences</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>