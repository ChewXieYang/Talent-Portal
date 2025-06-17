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

// Get user's talents for the dropdown
$talents_stmt = $conn->prepare("
    SELECT ut.id, ut.talent_title, tc.category_name 
    FROM user_talents ut
    JOIN talent_categories tc ON ut.category_id = tc.id
    WHERE ut.user_id = ?
    ORDER BY ut.talent_title
");
$talents_stmt->bind_param("i", $user_id);
$talents_stmt->execute();
$talents_result = $talents_stmt->get_result();

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $talent_id = !empty($_POST['talent_id']) ? intval($_POST['talent_id']) : null;
   
    // Validation
    if (empty($title)) {
        $message = 'Title is required.';
        $messageType = 'error';
    } else if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $message = 'Please select a valid file to upload.';
        $messageType = 'error';
    } else {
        $file = $_FILES['file'];
        
        // File validation
        $allowed_types = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'video/mp4', 'video/avi', 'video/mov', 'video/wmv',
            'audio/mp3', 'audio/wav', 'audio/ogg',
            'application/pdf', 'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain'
        ];
        
        $max_size = 50 * 1024 * 1024; // 50MB
        
        // Get file info
        $file_size = $file['size'];
        $file_type_mime = mime_content_type($file['tmp_name']);
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Determine file type category
        $file_type = 'other';
        if (strpos($file_type_mime, 'image/') === 0) {
            $file_type = 'image';
        } elseif (strpos($file_type_mime, 'video/') === 0) {
            $file_type = 'video';
        } elseif (strpos($file_type_mime, 'audio/') === 0) {
            $file_type = 'audio';
        } elseif (in_array($file_type_mime, ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'])) {
            $file_type = 'document';
        }
        
        // Validation checks
        if (!in_array($file_type_mime, $allowed_types)) {
            $message = 'File type not allowed. Please upload images, videos, audio files, or documents only.';
            $messageType = 'error';
        } elseif ($file_size > $max_size) {
            $message = 'File is too large. Maximum size is 50MB.';
            $messageType = 'error';
        } else {
            // Create upload directories
            $upload_dir = 'uploads/talentupload/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $unique_name;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $thumbnail_url = null;
                
                
                // Insert into database
                $insert_stmt = $conn->prepare("
                    INSERT INTO talent_uploads 
                    (user_id, talent_id, title, description, file_url, thumbnail_url, file_type, file_size, is_featured) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert_stmt->bind_param("iisssssii", 
                    $user_id, $talent_id, $title, $description, 
                    $file_path, $thumbnail_url, $file_type, $file_size, $is_featured
                );
                
                if ($insert_stmt->execute()) {
                    $message = 'File uploaded successfully!';
                    $messageType = 'success';
                    
                    // Log activity
                    $log_stmt = $conn->prepare("INSERT INTO activity_log (user_id, action_type, action_description) VALUES (?, ?, ?)");
                    $action_type = 'talent_uploads';
                    $action_desc = 'Uploaded talent item: ' . $title;
                    $log_stmt->bind_param("iss", $user_id, $action_type, $action_desc);
                    $log_stmt->execute();
                    
                    // Clear form data on success
                    $_POST = array();
                } else {
                    $message = 'Database error: ' . $insert_stmt->error;
                    $messageType = 'error';
                    // Delete uploaded file on database error
                    unlink($file_path);
                    if ($thumbnail_url && file_exists($thumbnail_url)) {
                        unlink($thumbnail_url);
                    }
                }
            } else {
                $message = 'Failed to upload file. Please try again.';
                $messageType = 'error';
            }
        }
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/upload.css">
    <title>Create Post - MMU Talent Showcase</title>
</head>
<body>
<div class="wrapper">
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
    <?php include 'includes/header.php'; ?>
    
    <div class="upload-container">
        <div class="page-header">
            <h1>Create Post</h1>
            <p>Share your creative work with the MMU community</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($talents_result->num_rows === 0): ?>
            <div class="no-talents">
                <strong>No talents found!</strong> You need to add at least one talent before uploading Talent items. 
                <a href="talents.php" style="color: #005eff;">Add talents here</a>.
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" required 
                       value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>"
                       placeholder="Give your post a title">
            </div>
            
            <div class="form-group">
                <label for="talent_id">Related Talent</label>
                <select id="talent_id" name="talent_id">
                    <option value="">Select a talent (optional)</option>
                    <?php 
                    $talents_result->data_seek(0);
                    while ($talent = $talents_result->fetch_assoc()): 
                    ?>
                        <option value="<?= $talent['id'] ?>" 
                                <?= (isset($_POST['talent_id']) && $_POST['talent_id'] == $talent['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($talent['talent_title']) ?> (<?= htmlspecialchars($talent['category_name']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
                <div class="help-text">Link this upload to one of your talents</div>
            </div>
            
            <div class="form-group">
                <label for="file">File <span class="required">*</span></label>
                <div class="file-upload-area" id="fileUploadArea">
                    <input type="file" id="file" name="file" required accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.txt">
                    <div class="upload-icon">📁</div>
                    <div class="upload-text">Click to select a file or drag and drop here</div>
                    <div class="file-info">
                        Supported: Images (JPG, PNG, GIF, WebP), Videos (MP4, AVI, MOV), 
                        Audio (MP3, WAV), Documents (PDF, DOC, TXT)<br>
                        Maximum size: 50MB
                    </div>
                </div>
                <div id="filePreview" class="file-preview" style="display: none;"></div>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" 
                          placeholder="Describe the post that you uploaded"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                <div class="help-text">Optional but recommended - helps others understand your work</div>
            </div>

            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Upload Post</button>
                <a href="profile.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    </div>
    <script>
        // File upload handling
        const fileInput = document.getElementById('file');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const filePreview = document.getElementById('filePreview');
        
        // File selection handler
        fileInput.addEventListener('change', handleFileSelect);
        
        // Drag and drop handlers
        fileUploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileUploadArea.classList.add('dragover');
        });
        
        fileUploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
        });
        
        fileUploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            fileUploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleFileSelect();
            }
        });
        
        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file) {
                displayFilePreview(file);
            } else {
                filePreview.style.display = 'none';
            }
        }
        
        function displayFilePreview(file) {
            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            const fileName = file.name;
            const fileType = file.type;
            
            let preview = `
                <strong>Selected file:</strong> ${fileName}<br>
                <strong>Size:</strong> ${fileSize} MB<br>
                <strong>Type:</strong> ${fileType}
            `;
            
            // Show image preview if it's an image
            if (fileType.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview += `<br><img src="${e.target.result}" style="max-width: 200px; max-height: 200px; margin-top: 10px; border-radius: 4px;">`;
                    filePreview.innerHTML = preview;
                };
                reader.readAsDataURL(file);
            } else {
                filePreview.innerHTML = preview;
            }
            
            filePreview.style.display = 'block';
        }
        
        // Form validation
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const file = document.getElementById('file').files[0];
            
            if (!title) {
                alert('Please enter a title for your upload.');
                e.preventDefault();
                return;
            }
            
            if (!file) {
                alert('Please select a file to upload.');
                e.preventDefault();
                return;
            }
            
            // Check file size (50MB)
            if (file.size > 50 * 1024 * 1024) {
                alert('File is too large. Maximum size is 50MB.');
                e.preventDefault();
                return;
            }
        });
    </script>
</div>
</body>
</html>