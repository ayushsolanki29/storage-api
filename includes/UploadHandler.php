<?php
class UploadHandler {
    private $db;
    private $auth;
    
    public function __construct() {
        $this->db = new Database();
        $this->auth = new Auth();
    }
    
    public function handleUpload($file, $userId, $compressionQuality = 80) {
        $conn = $this->db->connect();
        
        // Get user and plan details
        $user = $this->auth->getCurrentUser();
        if (!$user) {
            $stmt = $conn->prepare("SELECT u.*, p.* FROM users u LEFT JOIN plans p ON u.plan_id = p.id WHERE u.id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Validate file size
        if ($file['size'] > $user['max_file_size']) {
            return ['success' => false, 'message' => 'File size exceeds limit'];
        }
        
        // Validate storage
        if (($user['storage_used'] + $file['size']) > $user['max_storage']) {
            return ['success' => false, 'message' => 'Storage limit exceeded'];
        }
        
        // Validate file type
        $allowedTypes = explode(',', $user['allowed_file_types'] ?? env('ALLOWED_FILE_TYPES'));
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'File type not allowed'];
        }
        
        // Create user directory
        $userDir = env('UPLOAD_PATH') . '/' . $userId;
        $yearMonth = date('Y/m');
        $uploadDir = $userDir . '/' . $yearMonth;
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
        $uniqueName = $baseName . '_' . uniqid() . '.' . $extension;
        $filePath = $uploadDir . '/' . $uniqueName;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['success' => false, 'message' => 'Failed to move uploaded file'];
        }
        
        $response = [
            'original_url' => env('APP_URL') . '/' . $filePath,
            'compressed_url' => null,
            'thumbnail_url' => null
        ];
        
        $isImage = isImage($file['type']);
        $compressedPath = null;
        $thumbnailPath = null;
        
        // Process image if it's an image
        if ($isImage) {
            // Compression
            $compressedPath = $this->compressImage($filePath, $compressionQuality);
            if ($compressedPath) {
                $response['compressed_url'] = env('APP_URL') . '/' . $compressedPath;
            }
            
            // Thumbnail generation
            if ($user['can_generate_thumbnails'] ?? true) {
                $thumbnailWidth = $user['max_thumbnail_width'] ?? env('THUMBNAIL_WIDTH', 200);
                $thumbnailPath = $this->generateThumbnail($filePath, $thumbnailWidth);
                if ($thumbnailPath) {
                    $response['thumbnail_url'] = env('APP_URL') . '/' . $thumbnailPath;
                }
            }
        }
        
        // Save to database
        $stmt = $conn->prepare("
            INSERT INTO files (user_id, original_name, stored_name, file_path, file_size, mime_type, compression_quality, thumbnail_path, is_compressed, is_image, width, height, upload_ip)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $dimensions = $isImage ? getimagesize($filePath) : [0, 0];
        
        $stmt->execute([
            $userId,
            $file['name'],
            $uniqueName,
            $filePath,
            $file['size'],
            $file['type'],
            $compressionQuality,
            $thumbnailPath,
            $compressedPath ? true : false,
            $isImage,
            $dimensions[0] ?? 0,
            $dimensions[1] ?? 0,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        $fileId = $conn->lastInsertId();
        
        // Log usage
        logUsage($userId, 'upload', $file['size'], $fileId, 'upload');
        
        $response['success'] = true;
        $response['file_id'] = $fileId;
        $response['file'] = [
            'id' => $fileId,
            'original_name' => $file['name'],
            'size' => $file['size'],
            'mime_type' => $file['type']
        ];
        
        return $response;
    }
    
    private function compressImage($sourcePath, $quality) {
        $mime = getMimeType($sourcePath);
        $image = null;
        
        switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($sourcePath);
                break;
            default:
                return null;
        }
        
        if (!$image) return null;
        
        $compressedPath = str_replace('.', '_compressed.', $sourcePath);
        
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($image, $compressedPath, $quality);
                break;
            case 'image/png':
                $quality = floor(($quality / 100) * 9); // PNG quality is 0-9
                imagepng($image, $compressedPath, $quality);
                break;
            case 'image/gif':
                imagegif($image, $compressedPath);
                break;
            case 'image/webp':
                imagewebp($image, $compressedPath, $quality);
                break;
        }
        
        imagedestroy($image);
        return $compressedPath;
    }
    
    private function generateThumbnail($sourcePath, $maxWidth) {
        list($width, $height) = getimagesize($sourcePath);
        
        if ($width <= $maxWidth) {
            return null; // No need for thumbnail
        }
        
        $newHeight = floor($height * ($maxWidth / $width));
        $mime = getMimeType($sourcePath);
        $sourceImage = null;
        
        switch ($mime) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                $sourceImage = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                return null;
        }
        
        if (!$sourceImage) return null;
        
        $thumbnail = imagecreatetruecolor($maxWidth, $newHeight);
        
        // Preserve transparency for PNG and GIF
        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagecolortransparent($thumbnail, imagecolorallocatealpha($thumbnail, 0, 0, 0, 127));
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }
        
        imagecopyresampled($thumbnail, $sourceImage, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
        
        $thumbnailPath = str_replace('.', '_thumb.', $sourcePath);
        
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($thumbnail, $thumbnailPath, 85);
                break;
            case 'image/png':
                imagepng($thumbnail, $thumbnailPath, 8);
                break;
            case 'image/gif':
                imagegif($thumbnail, $thumbnailPath);
                break;
            case 'image/webp':
                imagewebp($thumbnail, $thumbnailPath, 85);
                break;
        }
        
        imagedestroy($sourceImage);
        imagedestroy($thumbnail);
        
        return $thumbnailPath;
    }
}
?>