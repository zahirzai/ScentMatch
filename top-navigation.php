<?php
// Check for database connection error
if (!isset($conn) || $conn->connect_error) {
    $conn = new mysqli("localhost", "root", "", "scentmatch3");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
}

// Initialize cart item count to 0 by default
$cartItemCount = 0;

// Ensure the user is logged in
if (isset($_SESSION['customerID'])) {
    $customer_id = $_SESSION['customerID'];

    // Fetch the cart item count
    $query = "SELECT SUM(Quantity) as item_count FROM manage_order WHERE CustomerID = ? AND PaymentStatus = 'Unpaid'";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $row = $result->fetch_assoc();
            $cartItemCount = $row['item_count'] ?? 0;
        }
        $stmt->close();
    }
}

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<head>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <script src="js/search.js"></script>
    <script src="js/top-bar.js"></script>
    <link rel="stylesheet" href="css/top-navigation.css">
</head>

<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <!-- Left Side: Email and Phone -->
        <div class="left-content">
            <i>
                <a href="mailto:support@scentmatch.com">support@scentmatch.com</a>
            </i>
            <i>
                <span>+60 12-345-6789</span>
            </i>
        </div>

        <!-- Right Side: My Account Dropdown -->
        <div class="right-content">
            <nav id="navmenu" class="navmenu">
                <ul>
                    <li class="dropdown">
                        <a href="#">
                            <span style="color: white;">My Account</span>
                            <i class="bi bi-chevron-down toggle-dropdown"></i>
                        </a>
                        <ul>
                            <?php if (isset($_SESSION['username'])): ?>
                                <li><a href="profile.php" style="color: black;"><?= htmlspecialchars($_SESSION['username']) ?></a></li>
                                <li><a href="logout.php" style="color: black;">Logout</a></li>
                            <?php else: ?>
                                <li><a href="auth.php" style="color: black;">Login</a></li>
                                <li><a href="auth.php?panel=register" style="color: black;">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <!-- Top Navigation Bar -->
    <div class="top-nav">
        <div class="left-nav">
            <a href="home.php">
                <img src="./img/sclogo.png" alt="sc_logo" class="sc-logo">
            </a>
            <div class="nav-links">
                <div class="dropdown"><a href="search.php?query=man">Men</a></div>
                <div class="dropdown"><a href="search.php?query=woman">Women</a></div>
                <div class="dropdown"><a href="search.php?query=unisex">Unisex</a></div>
            </div>
        </div>

        <!-- Mobile Menu Button -->
        <button class="mobile-menu-btn">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Right Navigation -->
        <div class="right-nav">
            <div class="nav-links">
                <div class="dropdown"><a href="all_product.php">Product</a></div>

                <div class="dropdown">
                    <a href="#">Scent</a>
                    <div class="dropdown-menu scent-menu">
                        <?php
                        // Fetch all scents from the database
                        $scent_query = "SELECT ScentID, ScentName FROM scents ORDER BY ScentName";
                        $scent_result = $conn->query($scent_query);
                        
                        if ($scent_result && $scent_result->num_rows > 0) {
                            while ($scent = $scent_result->fetch_assoc()) {
                                echo '<a href="search.php?query=' . urlencode($scent['ScentName']) . '&scent_types[]=' . $scent['ScentID'] . '&from_top_nav=true">' . htmlspecialchars($scent['ScentName']) . '</a>';
                            }
                        }
                        ?>
                    </div>
                </div>

                <div class="dropdown">
                    <a href="#">Eau de Parfum</a>
                    <div class="dropdown-menu scent-menu">
                        <?php
                        // Fetch all concentrations from the database
                        $concentration_query = "SELECT ConcentrationID, ConcentrationName FROM concentration ORDER BY ConcentrationName";
                        $concentration_result = $conn->query($concentration_query);
                        
                        if ($concentration_result && $concentration_result->num_rows > 0) {
                            while ($concentration = $concentration_result->fetch_assoc()) {
                                echo '<a href="search.php?query=' . urlencode($concentration['ConcentrationName']) . '">' . htmlspecialchars($concentration['ConcentrationName']) . '</a>';
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Search Button and Expanding Input -->
            <div class="search-container">
                <button type="button" class="search-button">
                    <i class="fas fa-search"></i>
                </button>
                <form action="search.php" method="GET">
                    <input type="text" name="query" class="search-input" placeholder="Search...">
                </form>
            </div>

            <!-- User Authentication and Cart -->
            <?php if (isset($_SESSION['username'])): ?>
                <a href="cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count" id="cartCount"><?php echo $cartItemCount; ?></span>
                </a>
                <a href="profile.php" class="user-name">
                    <?php echo htmlspecialchars($_SESSION['username']); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu">
        <div class="mobile-menu-content">
            <div class="mobile-search-container">
                <form action="search.php" method="GET">
                    <input type="text" name="query" class="mobile-search-input" placeholder="Search...">
                    <button type="submit" class="mobile-search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            <a href="home.php">Home</a>
            <a href="search.php?query=man">Men</a>
            <a href="search.php?query=woman">Women</a>
            <a href="search.php?query=unisex">Unisex</a>
            <a href="all_product.php">Product</a>
            <div class="mobile-dropdown">
                <a href="#" class="mobile-dropdown-toggle">Scent</a>
                <div class="mobile-dropdown-content">
                    <?php
                    if ($scent_result && $scent_result->num_rows > 0) {
                        $scent_result->data_seek(0); // Reset the result pointer
                        while ($scent = $scent_result->fetch_assoc()) {
                            echo '<a href="search.php?query=' . urlencode($scent['ScentName']) . '&scent_types[]=' . $scent['ScentID'] . '&from_top_nav=true">' . htmlspecialchars($scent['ScentName']) . '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
            <div class="mobile-dropdown">
                <a href="#" class="mobile-dropdown-toggle">Eau de Parfum</a>
                <div class="mobile-dropdown-content">
                    <?php
                    if ($concentration_result && $concentration_result->num_rows > 0) {
                        $concentration_result->data_seek(0); // Reset the result pointer
                        while ($concentration = $concentration_result->fetch_assoc()) {
                            echo '<a href="search.php?query=' . urlencode($concentration['ConcentrationName']) . '">' . htmlspecialchars($concentration['ConcentrationName']) . '</a>';
                        }
                    }
                    ?>
                </div>
            </div>
            <?php if (isset($_SESSION['username'])): ?>
                <a href="profile.php" class="mobile-auth-link">
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['username']) ?>
                </a>
                <a href="logout.php" class="mobile-auth-link mobile-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php else: ?>
                <a href="auth.php" class="mobile-auth-link">Login</a>
                <a href="auth.php?panel=register" class="mobile-auth-link">Register</a>
            <?php endif; ?>
            <div class="mobile-contact-info">
                <a href="mailto:contact@example.com"><i class="fas fa-envelope"></i> contact@example.com</a>
                <span><i class="fas fa-phone"></i> +1 5589 55488 55</span>
            </div>
        </div>
    </div>

    <script>
    // Existing cart count update function
    function updateCartCount(count) {
        const cartCountElement = document.getElementById('cartCount');
        if (cartCountElement) {
            cartCountElement.textContent = count;
            if (count > 0) {
                cartCountElement.style.display = 'block';
            } else {
                cartCountElement.style.display = 'none';
            }
        }
    }

    // Mobile menu functionality
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const mobileMenu = document.querySelector('.mobile-menu');
        const mobileDropdowns = document.querySelectorAll('.mobile-dropdown-toggle');

        // Function to close the mobile menu
        function closeMobileMenu() {
            mobileMenuBtn.classList.remove('active');
            mobileMenu.classList.remove('active');
            document.body.classList.remove('menu-open');
        }

        // Add a check to ensure mobileMenuBtn exists before adding event listener
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function(event) {
                event.stopPropagation(); // Prevent the click from immediately closing the menu
                this.classList.toggle('active');
                mobileMenu.classList.toggle('active');
                document.body.classList.toggle('menu-open');
            });

            // Close mobile menu when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInsideMenu = mobileMenu.contains(event.target);
                const isClickOnButton = mobileMenuBtn.contains(event.target);
                
                if (!isClickInsideMenu && !isClickOnButton && mobileMenu.classList.contains('active')) {
                    closeMobileMenu();
                }
            });
        }

        mobileDropdowns.forEach(dropdown => {
            dropdown.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation(); // Prevent dropdown click from closing main menu
                const parent = this.parentElement;
                parent.classList.toggle('active');
            });
        });
    });

    // Listen for messages from other pages
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type === 'cartUpdate') {
            updateCartCount(event.data.count);
        }
    });
    </script>
</body>
</html>