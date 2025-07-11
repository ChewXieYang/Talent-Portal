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
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <title>My Profile - MMU Talent Showcase</title>

</head>
<body>
    <div class="wrapper">
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
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
                    <form id="profileForm" method="POST" action="profile.php" enctype="multipart/form-data">
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