<?php
session_start();
$conn = new mysqli("localhost", "root", "", "scentmatch3");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect if not logged in
if (!isset($_SESSION['customerID'])) {
    header("Location: login.php");
    exit();
}

$customerID = $_SESSION['customerID'];
$fromProfile = isset($_GET['fromProfile']) && $_GET['fromProfile'] == 'true';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Skip button handling
    if (isset($_POST["skip"])) {
        $stmt = $conn->prepare("UPDATE customer SET PreferencesCompleted = 0 WHERE CustomerID = ?");
        $stmt->bind_param("i", $customerID);
        $stmt->execute();
        $stmt->close();
        
        // Set welcome message flag for skipped users
        $_SESSION['show_welcome'] = true;
        
        // Ensure session is maintained and redirect to home
        $_SESSION["customerID"] = $customerID;
        header("Location: home.php");
        exit();
    }

    // Process form data
    $gender = $_POST["gender"] ?? null;
    $lifestyle = $_POST["lifestyle"] ?? null;
    $scentPreferences = isset($_POST["scent_preferences"]) ? array_filter(explode(',', $_POST["scent_preferences"])) : [];
    $personality = $_POST["personality"] ?? null;
    $concentrationID = $_POST["concentration"] ?? 2;

    if ($gender && $lifestyle && !empty($scentPreferences) && $personality) {
        $conn->begin_transaction();
        try {
            // Insert into question table
            $stmt = $conn->prepare("INSERT INTO question (gender, ConcentrationID, LifestyleID, Personality) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siis", $gender, $concentrationID, $lifestyle, $personality);
            $stmt->execute();
            $questionID = $stmt->insert_id;
            $stmt->close();

            // Insert scent preferences
            $stmt = $conn->prepare("INSERT INTO question_scent (questionID, ScentID) VALUES (?, ?)");
            foreach ($scentPreferences as $scentID) {
                $stmt->bind_param("ii", $questionID, $scentID);
                $stmt->execute();
            }
            $stmt->close();

            // Link to customer
            $stmt = $conn->prepare("INSERT INTO preference (customerID, questionID) VALUES (?, ?)");
            $stmt->bind_param("ii", $customerID, $questionID);
            $stmt->execute();
            $stmt->close();

            // Mark as completed
            $stmt = $conn->prepare("UPDATE customer SET PreferencesCompleted = 1 WHERE CustomerID = ?");
            $stmt->bind_param("i", $customerID);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            
            // Set welcome message flag for new users
            $_SESSION['show_welcome'] = true;
            
            // Ensure session is maintained and redirect to home
            $_SESSION["customerID"] = $customerID;
            header("Location: home.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = "Error saving preferences: " . $e->getMessage();
            header("Location: question.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Please complete all required fields";
        header("Location: question.php");
        exit();
    }
}

// Fetch scent and lifestyle options
$scents = $conn->query("SELECT * FROM scents")->fetch_all(MYSQLI_ASSOC);
$lifestyles = $conn->query("SELECT * FROM lifestyle")->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Your Perfect Scent</title>
    <link rel="stylesheet" href="css/question.css">
    <link rel="stylesheet" href="css/top-navigation.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #ffffff;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }

        .error-message {
            color: #ff0000;
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: rgba(255, 0, 0, 0.1);
            border-radius: 8px;
            animation: fadeIn 0.3s ease-in-out;
        }

        #questionnaireForm {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: #000000;
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .button-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .button {
            padding: 15px 20px;
            border: 2px solid #000000;
            border-radius: 12px;
            background: white;
            color: #000000;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            position: relative;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: #ff0000;
            color: #ff0000;
        }

        .button.selected {
            background: #ff0000;
            color: white;
            border-color: #ff0000;
            animation: pulse 0.3s ease-in-out;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 40px;
        }

        .submit-button, .skip-button {
            padding: 15px 40px;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
        }

        .submit-button {
            background: #000000;
            color: white;
        }

        .submit-button:disabled {
            background: #666666;
            cursor: not-allowed;
            transform: none;
        }

        .skip-button {
            background: #ff0000;
            color: white;
        }

        .submit-button:hover:not(:disabled), .skip-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .submit-button:hover:not(:disabled) {
            background: #333333;
        }

        .skip-button:hover {
            background: #cc0000;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e0e0e0;
            border-radius: 3px;
            margin-bottom: 30px;
            overflow: hidden;
        }

        .progress {
            height: 100%;
            background: #ff0000;
            width: 0%;
            transition: width 0.3s ease;
        }

        .question-section {
            display: none;
            animation: fadeIn 0.5s ease-in-out;
        }

        .question-section.active {
            display: block;
        }

        .scent-btn.selected::after {
            content: '✓';
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
        }

        .personality-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .personality-btn i {
            font-size: 1.2rem;
        }

        /* Update button colors in JavaScript */
        .nextButton {
            background-color: #000000 !important;
        }

        .nextButton:disabled {
            background-color: #666666 !important;
        }

        .scent-btn {
            position: relative;
        }

        .scent-btn .tooltip {
            visibility: hidden;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #000; /* Black background */
            color: #fff; /* White text */
            text-align: center;
            padding: 10px 15px;
            border-radius: 6px;
            width: 200px;
            font-size: 0.9rem;
            line-height: 1.4;
            z-index: 10; /* Increased z-index */
            opacity: 0;
            transition: opacity 0.3s, visibility 0.3s;
            margin-bottom: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .scent-btn .tooltip::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }

        .scent-btn:hover .tooltip {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>

<body>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form id="questionnaireForm" action="" method="POST">
        <input type="hidden" name="concentration" value="2">
        
        <div class="progress-bar">
            <div class="progress" id="progressBar"></div>
        </div>

        <!-- Gender Section -->
        <div class="question-section active" id="genderSection">
            <h2>What is your gender?</h2>
            <div class="button-group">
                <button type="button" class="button gender-btn" data-value="man">
                    <i class="fas fa-mars"></i> Man
                </button>
                <button type="button" class="button gender-btn" data-value="woman">
                    <i class="fas fa-venus"></i> Woman
                </button>
                <input type="hidden" name="gender" id="genderInput">
            </div>
        </div>

        <!-- Lifestyle Section -->
        <div class="question-section" id="lifestyleSection">
            <h2>What's your ideal way to spend a weekend?</h2>
            <div class="button-group">
                <?php foreach ($lifestyles as $lifestyle): ?>
                    <button type="button" class="button lifestyle-btn" data-value="<?php echo $lifestyle['LifestyleID']; ?>">
                        <?php echo htmlspecialchars($lifestyle['Description']); ?>
                    </button>
                <?php endforeach; ?>
                <input type="hidden" name="lifestyle" id="lifestyleInput">
            </div>
        </div>

        <!-- Scent Preferences Section -->
        <div class="question-section" id="scentSection">
            <h2>What type of scent do you like? (Choose up to 3)</h2>
            <div class="button-group">
                <?php foreach ($scents as $scent): ?>
                    <button type="button" class="button scent-btn" data-value="<?php echo $scent['ScentID']; ?>">
                        <?php echo htmlspecialchars($scent['ScentName']); ?>
                        <span class="tooltip"><?php echo htmlspecialchars($scent['Description']); ?></span>
                    </button>
                <?php endforeach; ?>
                <input type="hidden" name="scent_preferences" id="scentInput">
            </div>
        </div>

        <!-- Personality Section -->
        <div class="question-section" id="personalitySection">
            <h2>How would you describe your personality?</h2>
            <div class="button-group">
                <button type="button" class="button personality-btn" data-value="bold">
                    <i class="fas fa-fire"></i> Bold & Confident
                </button>
                <button type="button" class="button personality-btn" data-value="calm">
                    <i class="fas fa-leaf"></i> Calm & Natural
                </button>
                <button type="button" class="button personality-btn" data-value="elegant">
                    <i class="fas fa-gem"></i> Elegant & Sophisticated
                </button>
                <button type="button" class="button personality-btn" data-value="mysterious">
                    <i class="fas fa-mask"></i> Mysterious & Enigmatic
                </button>
                <button type="button" class="button personality-btn" data-value="playful">
                    <i class="fas fa-sun"></i> Energetic & Playful
                </button>
                <input type="hidden" name="personality" id="personalityInput">
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="action-buttons">
            <button type="button" id="prevButton" class="skip-button" style="display: none;">Previous</button>
            <button type="button" id="nextButton" class="submit-button">Next</button>
            <button type="submit" id="submitButton" class="submit-button" style="display: none;">Submit</button>
            <button type="submit" name="skip" class="skip-button">Skip</button>
        </div>
    </form>

    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.question-section');
            const progressBar = document.getElementById('progressBar');
            const prevButton = document.getElementById('prevButton');
            const nextButton = document.getElementById('nextButton');
            const submitButton = document.getElementById('submitButton');
            let currentSection = 0;

            const genderButtons = document.querySelectorAll('.gender-btn');
            const lifestyleButtons = document.querySelectorAll('.lifestyle-btn');
            const scentButtons = document.querySelectorAll('.scent-btn');
            const personalityButtons = document.querySelectorAll('.personality-btn');

            let selectedGender = null;
            let selectedLifestyle = null;
            const selectedScents = new Set();
            const selectedPersonalities = new Set();

            function updateProgress() {
                const progress = ((currentSection + 1) / sections.length) * 100;
                progressBar.style.width = `${progress}%`;
            }

            function showSection(index) {
                sections.forEach(section => section.classList.remove('active'));
                sections[index].classList.add('active');
                
                prevButton.style.display = index > 0 ? 'block' : 'none';
                nextButton.style.display = index < sections.length - 1 ? 'block' : 'none';
                submitButton.style.display = index === sections.length - 1 ? 'block' : 'none';
                
                updateProgress();
            }

            function checkSectionCompletion() {
                let isComplete = false;
                switch(currentSection) {
                    case 0:
                        isComplete = selectedGender !== null;
                        break;
                    case 1:
                        isComplete = selectedLifestyle !== null;
                        break;
                    case 2:
                        isComplete = selectedScents.size > 0;
                        break;
                    case 3:
                        isComplete = selectedPersonalities.size > 0;
                        break;
                }
                nextButton.disabled = !isComplete;
                nextButton.style.backgroundColor = isComplete ? "#000000" : "#666666";
            }

            prevButton.addEventListener('click', () => {
                if (currentSection > 0) {
                    currentSection--;
                    showSection(currentSection);
                }
            });

            nextButton.addEventListener('click', () => {
                if (currentSection < sections.length - 1) {
                    currentSection++;
                    showSection(currentSection);
                }
            });

            genderButtons.forEach(button => {
                button.addEventListener('click', function() {
                    genderButtons.forEach(btn => btn.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedGender = this.dataset.value;
                    document.getElementById('genderInput').value = selectedGender;
                    checkSectionCompletion();
                });
            });

            lifestyleButtons.forEach(button => {
                button.addEventListener('click', function() {
                    lifestyleButtons.forEach(btn => btn.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedLifestyle = this.dataset.value;
                    document.getElementById('lifestyleInput').value = selectedLifestyle;
                    checkSectionCompletion();
                });
            });

            scentButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const scentValue = this.dataset.value;
                    if (selectedScents.has(scentValue)) {
                        selectedScents.delete(scentValue);
                        this.classList.remove('selected');
                    } else {
                        if (selectedScents.size >= 3) {
                            // Get the first (oldest) selected scent
                            const oldestScent = Array.from(selectedScents)[0];
                            // Remove the oldest selection
                            selectedScents.delete(oldestScent);
                            // Remove the selected class from the oldest button
                            document.querySelector(`.scent-btn[data-value="${oldestScent}"]`).classList.remove('selected');
                        }
                        // Add the new selection
                        selectedScents.add(scentValue);
                        this.classList.add('selected');
                    }
                    document.getElementById('scentInput').value = Array.from(selectedScents).join(',');
                    checkSectionCompletion();
                });
            });

            personalityButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const personalityValue = this.dataset.value;
                    if (selectedPersonalities.has(personalityValue)) {
                        selectedPersonalities.delete(personalityValue);
                        this.classList.remove('selected');
                    } else {
                        // Remove selection from all other buttons
                        personalityButtons.forEach(btn => {
                            btn.classList.remove('selected');
                            selectedPersonalities.delete(btn.dataset.value);
                        });
                        // Add new selection
                        selectedPersonalities.add(personalityValue);
                        this.classList.add('selected');
                    }
                    document.getElementById('personalityInput').value = Array.from(selectedPersonalities).join(',');
                    checkSectionCompletion();
                });
            });

            // Initialize
            showSection(0);
            checkSectionCompletion();
        });
    </script>
</body>
</html>