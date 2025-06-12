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
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
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
            $upload_dir = 'uploads/portfolio/';
            $thumbnails_dir = 'uploads/thumbnails/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            if (!is_dir($thumbnails_dir)) {
                mkdir($thumbnails_dir, 0777, true);
            }
            
            // Generate unique filename
            $unique_name = uniqid() . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $unique_name;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                $thumbnail_url = null;
                
                // Create thumbnail for images
                if ($file_type === 'image') {
                    $thumbnail_name = 'thumb_' . $unique_name;
                    $thumbnail_path = $thumbnails_dir . $thumbnail_name;
                    
                    if (createThumbnail($file_path, $thumbnail_path, 300, 300)) {
                        $thumbnail_url = $thumbnail_path;
                    }
                }
                
                // Insert into database
                $insert_stmt = $conn->prepare("
                    INSERT INTO portfolio_items 
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
                    $action_type = 'portfolio_upload';
                    $action_desc = 'Uploaded portfolio item: ' . $title;
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

// Function to create thumbnail for images
function createThumbnail($source_path, $thumb_path, $max_width = 300, $max_height = 300) {
    $image_info = getimagesize($source_path);
    if (!$image_info) return false;
    
    $source_type = $image_info['mime'];
    $source_width = $image_info[0];
    $source_height = $image_info[1];
    
    // Calculate new dimensions
    $ratio = min($max_width / $source_width, $max_height / $source_height);
    $new_width = round($source_width * $ratio);
    $new_height = round($source_height * $ratio);
    
    // Create source image resource
    switch ($source_type) {
        case 'image/jpeg':
            $source_image = imagecreatefromjpeg($source_path);
            break;
        case 'image/png':
            $source_image = imagecreatefrompng($source_path);
            break;
        case 'image/gif':
            $source_image = imagecreatefromgif($source_path);
            break;
        case 'image/webp':
            $source_image = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }
    
    if (!$source_image) return false;
    
    // Create new image
    $thumb_image = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($source_type == 'image/png' || $source_type == 'image/gif') {
        imagecolortransparent($thumb_image, imagecolorallocatealpha($thumb_image, 0, 0, 0, 127));
        imagealphablending($thumb_image, false);
        imagesavealpha($thumb_image, true);
    }
    
    // Copy and resize
    imagecopyresampled($thumb_image, $source_image, 0, 0, 0, 0, 
                      $new_width, $new_height, $source_width, $source_height);
    
    // Save thumbnail
    $result = false;
    switch ($source_type) {
        case 'image/jpeg':
            $result = imagejpeg($thumb_image, $thumb_path, 85);
            break;
        case 'image/png':
            $result = imagepng($thumb_image, $thumb_path);
            break;
        case 'image/gif':
            $result = imagegif($thumb_image, $thumb_path);
            break;
        case 'image/webp':
            $result = imagewebp($thumb_image, $thumb_path, 85);
            break;
    }
    
    // Clean up
    imagedestroy($source_image);
    imagedestroy($thumb_image);
    
    return $result;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Portfolio - MMU Talent Showcase</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .upload-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #666;
            font-size: 16px;
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
        
        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #005eff;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .file-upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            transition: border-color 0.3s;
            cursor: pointer;
            position: relative;
        }
        
        .file-upload-area:hover {
            border-color: #005eff;
        }
        
        .file-upload-area.dragover {
            border-color: #005eff;
            background-color: #f0f8ff;
        }
        
        .file-upload-area input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #999;
            margin-bottom: 15px;
        }
        
        .upload-text {
            color: #666;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .file-info {
            font-size: 14px;
            color: #999;
        }
        
        .file-preview {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
            border: 1px solid #e9ecef;
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
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn-primary {
            background: #005eff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0044cc;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .no-talents {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 4px;
            color: #856404;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="upload-container">
        <div class="page-header">
            <h1>Upload Portfolio Item</h1>
            <p>Share your creative work with the MMU community</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($talents_result->num_rows === 0): ?>
            <div class="no-talents">
                <strong>No talents found!</strong> You need to add at least one talent before uploading portfolio items. 
                <a href="talents.php" style="color: #005eff;">Add talents here</a>.
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" id="uploadForm">
            <div class="form-group">
                <label for="title">Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" required 
                       value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>"
                       placeholder="Give your work a descriptive title">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" 
                          placeholder="Describe your work, techniques used, inspiration, etc."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                <div class="help-text">Optional but recommended - helps others understand your work</div>
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
                <div class="checkbox-group">
                    <input type="checkbox" id="is_featured" name="is_featured" value="1"
                           <?= (isset($_POST['is_featured']) && $_POST['is_featured']) ? 'checked' : '' ?>>
                    <label for="is_featured">Feature this item</label>
                </div>
                <div class="help-text">Featured items appear first in your portfolio</div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Upload File</button>
                <a href="profile.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
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
</body>
</html>