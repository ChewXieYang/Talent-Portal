</main>
<footer>
    <div class="sidebar">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="login.php">Sign in</a>
        <?php endif; ?>
        <a href="student_dashboard.php">🏠 Home</a>
        <a href="catalog.php">🎨 Catalog</a>
        <a href="profile.php">😊 Profile</a>
        <a href="upload.php">📤 Upload</a>
    </div>
</footer>
</body>
</html>