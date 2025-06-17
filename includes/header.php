<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MMU Talent Showcase</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main>
    <header>
    <div class="header-container">
        <a href="index.php" class="logo"> MMU Talent Showcase</a>
        
        <nav class="nav-menu">
            <a href="index.php"> Home</a>
            <a href="catalog.php"> Catalog</a>
            <a href="services.php">Services Marketplace</a>
            <a href="forum.php"> Forum</a>
            <a href="message_board.php"> Message Board</a>
            <a href="news.php"> News</a>
            <a href="cart.php" class="cart-icon">
                <span class="cart-count" id="headerCartCount">0</span>
            </a>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if ($_SESSION['user_type'] === 'admin'): ?>
                    <a href="admin/dashboard.php"> Admin</a>
                <?php else: ?>
                    <a href="profile.php"> Profile</a>
                    <a href="my_portfolio.php"> Portfolio</a>
                    <a href="upload.php">⬆ Upload</a>
                    <a href="talents.php"> Talents</a>
                    <a href="my_services.php"> My Services</a>
                    <a href="my_orders.php"> My Orders</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="register.php"> Register</a>
                <a href="login.php"> Login</a>
            <?php endif; ?>
        </nav>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-section">
                <div class="user-info">
                    Welcome, <strong><?= isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'User' ?></strong>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        <?php endif; ?>
    </div>
</header>  
</main>
<script>
// Update cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    updateHeaderCartCount();
});

function updateHeaderCartCount() {
    <?php if (isset($_SESSION['user_id'])): ?>
    fetch('cart_actions.php?action=count')
    .then(response => response.json())
    .then(data => {
        const cartCount = document.getElementById('headerCartCount');
        if (cartCount) {
            cartCount.textContent = data.count || 0;
            // Hide badge if count is 0
            cartCount.style.display = (data.count > 0) ? 'block' : 'none';
        }
    })
    .catch(error => console.error('Error updating cart count:', error));
    <?php endif; ?>
}

// Call this function when items are added to cart from other pages
window.updateCartCount = updateHeaderCartCount;

function toggleMobileMenu() {
    const navLinks = document.getElementById('navLinks');
    navLinks.classList.toggle('show');
}

function toggleUserMenu() {
    const dropdown = document.getElementById('userMenuDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

// Close user menu when clicking outside
document.addEventListener('click', function(event) {
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.getElementById('userMenuDropdown');
    
    if (dropdown && userMenu && !userMenu.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        document.getElementById('navLinks').classList.remove('show');
    });
});
</script>
