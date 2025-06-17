</main>
<footer>
    <div class="sidebar">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="login.php">Sign in</a>
        <?php endif; ?>
        <?php if ($_SESSION['user_type'] === 'admin'): ?>
                <a href="admin/dashboard.php"> Admin</a>
        <?php else: ?>
        <a href="student_dashboard.php">🏠 Home</a>
        <a href="catalog.php">🎨 Catalog</a>
        <a href="forum.php">💬 Forum</a>
        <a href="services.php">Services Marketplace</a>
        <a href="news.php">📰 News</a>
        <a href="message_board.php"> Message Board</a>
        <a href="cart.php" class="cart-icon">
                <span class="cart-count" id="headerCartCount">0</span>
        </a>

        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php">Profile</a>
            <a href="my_portfolio.php"> Portfolio</a>
            <a href="upload.php">⬆ Upload</a>
            <a href="talents.php"> Talents</a>
            <a href="my_services.php"> My Services</a>
            <a href="my_orders.php"> My Orders</a>
        <?php endif; ?>

    </div>
</footer>
</body>
</html>