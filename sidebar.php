<?php

?>
<div class="sidebar">
    <ul>
        <li class="<?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <a href="profile.php">Dashboard</a>
        </li>
        <li class="<?php echo $current_page == 'order.php' ? 'active' : ''; ?>">
            <a href="order.php">Orders</a>
        </li>
        <li class="<?php echo $current_page == 'address.php' ? 'active' : ''; ?>">
            <a href="address.php">Address</a>
        </li>
        <li class="<?php echo $current_page == 'account_details.php' ? 'active' : ''; ?>">
            <a href="account_details.php">Account Details</a>
        </li>
        <li class="<?php echo $current_page == 'feedback_respond.php' ? 'active' : ''; ?>">
            <a href="feedback_respond.php">Feedback Responses</a>
        </li>
        <li>
            <a href="logout.php">Logout</a>
        </li>
    </ul>
</div>