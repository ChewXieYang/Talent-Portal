<?php
include 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Get talent categories
$categories_stmt = $conn->prepare("SELECT * FROM talent_categories ORDER BY category_name");
$categories_stmt->execute();
$categories_result = $categories_stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_talent'])) {
        // Add new talent
        $category_id = intval($_POST['category_id']);
        $talent_title = trim($_POST['talent_title']);
        $talent_description = trim($_POST['talent_description']);
        $skill_level = $_POST['skill_level'];
        $years_experience = intval($_POST['years_experience']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        
        if (empty($talent_title) || $category_id <= 0) {
            $message = 'Please fill in all required fields.';
            $messageType = 'error';
        } else {
            $insert_stmt = $conn->prepare("
                INSERT INTO user_talents (user_id, category_id, talent_title, talent_description, skill_level, years_experience, is_featured) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $insert_stmt->bind_param("iisssii", $user_id, $category_id, $talent_title, $talent_description, $skill_level, $years_experience, $is_featured);
            
            if ($insert_stmt->execute()) {
                $message = 'Talent added successfully!';
                $messageType = 'success';
                
                // Log activity
                $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_description) VALUES (?, ?, ?)");
                $action_type = 'talent_add';
                $action_desc = 'Added new talent: ' . $talent_title;
                $log_stmt->bind_param("iss", $user_id, $action_type, $action_desc);
                $log_stmt->execute();
            } else {
                $message = 'Error adding talent: ' . $insert_stmt->error;
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['delete_talent'])) {
        // Delete talent
        $talent_id = intval($_POST['talent_id']);
        
        $delete_stmt = $conn->prepare("DELETE FROM user_talents WHERE id = ? AND user_id = ?");
        $delete_stmt->bind_param("ii", $talent_id, $user_id);
        
        if ($delete_stmt->execute()) {
            $message = 'Talent deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting talent.';
            $messageType = 'error';
        }
    }
}

// Get user's current talents
$talents_stmt = $conn->prepare("
    SELECT ut.*, tc.category_name 
    FROM user_talents ut
    JOIN talent_categories tc ON ut.category_id = tc.id
    WHERE ut.user_id = ?
    ORDER BY ut.is_featured DESC, ut.created_at DESC
");
$talents_stmt->bind_param("i", $user_id);
$talents_stmt->execute();
$talents_result = $talents_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Talents - MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
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
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
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
        
        .required {
            color: #dc3545;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #005eff;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .talents-grid {
            display: grid;
            gap: 20px;
        }
        
        .talent-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
            position: relative;
        }
        
        .talent-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .talent-title {
            font-weight: bold;
            color: #333;
            font-size: 1.1em;
        }
        
        .talent-category {
            display: inline-block;
            padding: 4px 12px;
            background: #007bff;
            color: white;
            border-radius: 15px;
            font-size: 12px;
        }
        
        .talent-meta {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .talent-description {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .talent-actions {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        
        .skill-level {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .skill-beginner { background: #ffc107; color: #856404; }
        .skill-intermediate { background: #28a745; color: white; }
        .skill-advanced { background: #17a2b8; color: white; }
        .skill-expert { background: #dc3545; color: white; }
        
        .featured-badge {
            background: #ff6b35;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 500;
            margin-left: 10px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Manage Your Talents</h1>
            <p>Add and organize your skills and expertise</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <!-- Add New Talent Section -->
        <div class="section">
            <h2 class="section-title">Add New Talent</h2>
            <form method="POST">
                <input type="hidden" name="add_talent" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category_id">Category <span class="required">*</span></label>
                        <select id="category_id" name="category_id" required>
                            <option value="">Select a category</option>
                            <?php 
                            $categories_result->data_seek(0);
                            while ($category = $categories_result->fetch_assoc()): 
                            ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="talent_title">Talent Title <span class="required">*</span></label>
                        <input type="text" id="talent_title" name="talent_title" required 
                               placeholder="e.g., Portrait Photography, Guitar Playing">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="talent_description">Description</label>
                    <textarea id="talent_description" name="talent_description" rows="3"
                              placeholder="Describe your skills, experience, and what makes you unique in this area"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="skill_level">Skill Level</label>
                        <select id="skill_level" name="skill_level">
                            <option value="beginner">Beginner</option>
                            <option value="intermediate" selected>Intermediate</option>
                            <option value="advanced">Advanced</option>
                            <option value="expert">Expert</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="years_experience">Years of Experience</label>
                        <input type="number" id="years_experience" name="years_experience" 
                               min="0" max="50" value="0" placeholder="0">
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1">
                        <label for="is_featured">Feature this talent</label>
                    </div>
                    <small style="color: #666;">Featured talents appear first on your profile</small>
                </div>
                
                <button type="submit" class="btn btn-primary">Add Talent</button>
            </form>
        </div>
        
        <!-- Current Talents Section -->
        <div class="section">
            <h2 class="section-title">Your Current Talents</h2>
            
            <?php if ($talents_result->num_rows > 0): ?>
                <div class="talents-grid">
                    <?php while ($talent = $talents_result->fetch_assoc()): ?>
                        <div class="talent-card">
                            <div class="talent-actions">
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Are you sure you want to delete this talent?')">
                                    <input type="hidden" name="delete_talent" value="1">
                                    <input type="hidden" name="talent_id" value="<?= $talent['id'] ?>">
                                    <button type="submit" class="btn btn-danger" style="font-size: 12px; padding: 5px 10px;">
                                        Delete
                                    </button>
                                </form>
                            </div>
                            
                            <div class="talent-header">
                                <div>
                                    <div class="talent-title">
                                        <?= htmlspecialchars($talent['talent_title']) ?>
                                        <?php if ($talent['is_featured']): ?>
                                            <span class="featured-badge">Featured</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="talent-category"><?= htmlspecialchars($talent['category_name']) ?></span>
                                </div>
                            </div>
                            
                            <div class="talent-meta">
                                <span class="skill-level skill-<?= $talent['skill_level'] ?>">
                                    <?= ucfirst($talent['skill_level']) ?>
                                </span>
                                • <?= $talent['years_experience'] ?> year<?= $talent['years_experience'] != 1 ? 's' : '' ?> experience
                                • Added <?= date('M j, Y', strtotime($talent['created_at'])) ?>
                            </div>
                            
                            <?php if ($talent['talent_description']): ?>
                                <div class="talent-description">
                                    <?= nl2br(htmlspecialchars($talent['talent_description'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No talents added yet</h3>
                    <p>Start by adding your first talent using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="profile.php" class="btn" style="background: #6c757d; color: white;">Back to Profile</a>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>