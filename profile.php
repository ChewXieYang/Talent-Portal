<?php
include 'includes/db.php';
include 'includes/functions.php';

// Check if user is logged in
requireLogin();

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name']);
    $contact_email = trim($_POST['contact_email']);
    $phone_number = trim($_POST['phone_number']);
    $short_bio = trim($_POST['short_bio']);
    
    // Validation
    if (empty($full_name)) {
        $message = 'Full name is required.';
        $messageType = 'error';
    } else if (!empty($contact_email) && !filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Invalid contact email format.';
        $messageType = 'error';
    } else {
        // Update profile
        $stmt = $conn->prepare('
            UPDATE users 
            SET full_name = ?, contact_email = ?, phone_number = ?, short_bio = ? 
            WHERE id = ?
        ');
        $stmt->bind_param('ssssi', $full_name, $contact_email, $phone_number, $short_bio, $user_id);
        
        if ($stmt->execute()) {
            $message = 'Profile updated successfully!';
            $messageType = 'success';
            logActivity('profile_update', 'Updated profile information');
        } else {
            $message = 'Error updating profile. Please try again.';
            $messageType = 'error';
        }
        $stmt->close();
    }
}

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_picture'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $upload_result = uploadFile($_FILES['profile_picture'], 'uploads/avatars/', $allowed_types);
        
        if ($upload_result['success']) {
            // Update database
            $stmt = $conn->prepare('UPDATE users SET profile_picture_url = ? WHERE id = ?');
            $stmt->bind_param('si', $upload_result['filepath'], $user_id);
            
            if ($stmt->execute()) {
                $message = 'Profile picture updated successfully!';
                $messageType = 'success';
                logActivity('profile_picture_update', 'Updated profile picture');
            } else {
                $message = 'Error updating profile picture.';
                $messageType = 'error';
                unlink($upload_result['filepath']); // Delete uploaded file on error
            }
            $stmt->close();
        } else {
            $message = $upload_result['error'];
            $messageType = 'error';
        }
    } else {
        $message = 'Please select a valid image file.';
        $messageType = 'error';
    }
}

// Get user details
$user = getUserDetails($user_id);

// Get user talents
$talents = getUserTalents($user_id);

// Get user portfolio items (latest 6)
$portfolio = getPortfolioItems($user_id, null, 6);

// Get statistics
$stats = [];
// Total portfolio items
$stmt = $conn->prepare('SELECT COUNT(*) as count FROM portfolio_items WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['portfolio_count'] = $result->fetch_assoc()['count'];

// Total views
$stmt = $conn->prepare('SELECT SUM(views) as total FROM portfolio_items WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_views'] = $result->fetch_assoc()['total'] ?? 0;

// Active services
$stmt = $conn->prepare('SELECT COUNT(*) as count FROM services WHERE user_id = ? AND is_available = 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats['service_count'] = $result->fetch_assoc()['count'];

// Total talents
$stats['talent_count'] = $talents->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
        }
        
        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-header {
            background: white;
            padding: 30px;
            border-radius: 4px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: background 0.3s;
        }
        
        .change-picture-btn:hover {
            background: #e0e0e0;
        }
        
        .profile-info {
            flex: 1;
        }
        
        .profile-info h1 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .profile-meta {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .profile-meta span {
            margin-right: 20px;
        }
        
        .profile-bio {
            color: #555;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .profile-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #005eff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #0044cc;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #005eff;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        
        .content-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-title {
            font-size: 20px;
            color: #333;
            margin: 0;
        }
        
        .talent-list {
            display: grid;
            gap: 15px;
        }
        
        .talent-item {
            border: 1px solid #e0e0e0;
            padding: 15px;
            border-radius: 4px;
            background: #fafafa;
        }
        
        .talent-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .talent-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        
        .talent-category {
            display: inline-block;
            padding: 4px 8px;
            background: #007bff;
            color: white;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .talent-description {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .portfolio-item {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .portfolio-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .portfolio-thumb {
            width: 100%;
            height: 150px;
            object-fit: cover;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .portfolio-info {
            padding: 10px;
        }
        
        .portfolio-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .portfolio-stats {
            font-size: 12px;
            color: #666;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            position: relative;
        }
        
        .close {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 28px;
            font-weight: bold;
            color: #aaa;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }
        
        .file-input-label {
            display: inline-block;
            padding: 10px 20px;
            background: #f0f0f0;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .file-input-label:hover {
            background: #e0e0e0;
        }
        
        @media (max-width: 768px) {
            .profile-top {
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            
            .profile-actions {
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="profile-container">
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <div class="profile-header">
            <div class="profile-top">
                <div class="profile-picture-section">
                    <img src="<?= htmlspecialchars($user['profile_picture_url'] ?: 'uploads/avatars/default-avatar.jpg') ?>" 
                         alt="Profile Picture" 
                         class="profile-picture">
                    <button class="change-picture-btn" onclick="openPictureModal()">Change Picture</button>
                </div>
                
                <div class="profile-info">
                    <h1><?= htmlspecialchars($user['full_name']) ?></h1>
                    <div class="profile-meta">
                        <span>@<?= htmlspecialchars($user['username']) ?></span>
                        <span><?= htmlspecialchars($user['student_id']) ?></span>
                        <span><?= htmlspecialchars($user['section']) ?></span>
                        <span>Joined <?= date('F Y', strtotime($user['created_at'])) ?></span>
                    </div>
                    <p class="profile-bio">
                        <?= nl2br(htmlspecialchars($user['short_bio'] ?: 'No bio added yet.')) ?>
                    </p>
                    <div class="profile-actions">
                        <button class="btn" onclick="openEditModal()">Edit Profile</button>
                        <a href="view_profile.php?id=<?= $user_id ?>" class="btn btn-secondary">View Public Profile</a>
                        <a href="talents.php" class="btn btn-secondary">Manage Talents</a>
                        <a href="upload.php" class="btn btn-secondary">Upload Portfolio</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['talent_count'] ?></div>
                <div class="stat-label">Talents</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['portfolio_count'] ?></div>
                <div class="stat-label">Portfolio Items</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($stats['total_views']) ?></div>
                <div class="stat-label">Total Views</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['service_count'] ?></div>
                <div class="stat-label">Active Services</div>
            </div>
        </div>
        
        <!-- Talents Section -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">My Talents</h2>
                <a href="talents.php" class="btn">Add Talent</a>
            </div>
            
            <?php if ($talents->num_rows > 0): ?>
                <div class="talent-list">
                    <?php while ($talent = $talents->fetch_assoc()): ?>
                        <div class="talent-item">
                            <div class="talent-header">
                                <div>
                                    <div class="talent-title"><?= htmlspecialchars($talent['talent_title']) ?></div>
                                    <span class="talent-category"><?= htmlspecialchars($talent['category_name']) ?></span>
                                </div>
                                <div>
                                    <small><?= $talent['years_experience'] ?> years • <?= ucfirst($talent['skill_level']) ?></small>
                                </div>
                            </div>
                            <p class="talent-description">
                                <?= nl2br(htmlspecialchars($talent['talent_description'])) ?>
                            </p>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>You haven't added any talents yet.</p>
                    <a href="talents.php" class="btn">Add Your First Talent</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Portfolio Section -->
        <div class="content-section">
            <div class="section-header">
                <h2 class="section-title">Recent Portfolio</h2>
                <a href="my_portfolio.php" class="btn">View All</a>
            </div>
            
            <?php if ($portfolio->num_rows > 0): ?>
                <div class="portfolio-grid">
                    <?php while ($item = $portfolio->fetch_assoc()): ?>
                        <div class="portfolio-item">
                            <?php if ($item['file_type'] == 'image'): ?>
                                <img src="<?= htmlspecialchars($item['thumbnail_url'] ?: $item['file_url']) ?>" 
                                     alt="<?= htmlspecialchars($item['title']) ?>" 
                                     class="portfolio-thumb">
                            <?php else: ?>
                                <div class="portfolio-thumb">
                                    <span style="font-size: 48px;">
                                        <?php
                                        $icons = [
                                            'video' => '🎥',
                                            'audio' => '🎵',
                                            'document' => '📄',
                                            'code' => '💻',
                                            'other' => '📎'
                                        ];
                                        echo $icons[$item['file_type']] ?? '📎';
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <div class="portfolio-info">
                                <div class="portfolio-title"><?= htmlspecialchars($item['title']) ?></div>
                                <div class="portfolio-stats">👁️ <?= $item['views'] ?> views</div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>You haven't uploaded any portfolio items yet.</p>
                    <a href="upload.php" class="btn">Upload Your First Work</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Profile</h2>
            <form method="POST">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" 
                           value="<?= htmlspecialchars($user['full_name']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="contact_email">Contact Email</label>
                    <input type="email" id="contact_email" name="contact_email" 
                           value="<?= htmlspecialchars($user['contact_email']) ?>"
                           placeholder="Public contact email (optional)">
                </div>
                
                <div class="form-group">
                    <label for="phone_number">Phone Number</label>
                    <input type="tel" id="phone_number" name="phone_number" 
                           value="<?= htmlspecialchars($user['phone_number']) ?>"
                           placeholder="Contact number (optional)">
                </div>
                
                <div class="form-group">
                    <label for="short_bio">Bio</label>
                    <textarea id="short_bio" name="short_bio" 
                              placeholder="Tell us about yourself..."><?= htmlspecialchars($user['short_bio']) ?></textarea>
                </div>
                
                <button type="submit" class="btn">Save Changes</button>
            </form>
        </div>
    </div>
    
    <!-- Change Picture Modal -->
    <div id="pictureModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePictureModal()">&times;</span>
            <h2>Change Profile Picture</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_picture" value="1">
                
                <div class="form-group">
                    <label>Choose a new profile picture</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="profile_picture" name="profile_picture" 
                               accept="image/*" required>
                        <label for="profile_picture" class="file-input-label">
                            Select Image
                        </label>
                    </div>
                    <p style="margin-top: 10px; color: #666; font-size: 14px;">
                        Accepted formats: JPG, PNG, GIF (Max 5MB)
                    </p>
                </div>
                
                <button type="submit" class="btn">Upload Picture</button>
            </form>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Modal functions
        function openEditModal() {
            document.getElementById('editModal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        function openPictureModal() {
            document.getElementById('pictureModal').style.display = 'block';
        }
        
        function closePictureModal() {
            document.getElementById('pictureModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
        
        // File input preview
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Select Image';
            e.target.nextElementSibling.textContent = fileName;
        });
    </script>
</body>
</html>