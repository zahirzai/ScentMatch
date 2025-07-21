<?php
// Footer content
?>
<footer class="footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3>About ScentMatch</h3>
            <p>Your perfect fragrance companion. Discover scents that match your personality and preferences.</p>
        </div>
        <div class="footer-section">
            <h3>Quick Links</h3>
            <ul>
                <li><a href="logged_home.php">Home</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="<?php echo isset($_SESSION['customerID']) ? 'order.php' : 'auth.php'; ?>">My Orders</a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3>Contact Us</h3>
            <ul>
                <li><i class="fas fa-envelope"></i> support@scentmatch.com</li>
                <li><i class="fas fa-phone"></i> +60 12-345-6789</li>
                <li><i class="fas fa-map-marker-alt"></i> Kuala Lumpur, Malaysia</li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2024 ScentMatch. All rights reserved.</p>
    </div>
</footer>

<style>
    .footer {
        background-color: #1a1a1a;
        color: #fff;
        padding: 40px 0 20px;
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
            text-align: left;
        }

        .footer-section ul li i {
            margin-right: 5px;
        }
    }
</style> 