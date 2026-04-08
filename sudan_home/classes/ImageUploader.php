<?php
/**
 * نظام رفع الصور - دار السودان
 * classes/ImageUploader.php
 */

class ImageUploader {
    
    private $uploadDir = 'uploads/properties/';
    private $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
    private $maxFileSize = 5 * 1024 * 1024; // 5MB
    
    public function __construct() {
        // إنشاء المجلد إذا لم يكن موجوداً
        if (!file_exists($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0755, true)) {
                error_log("Failed to create upload directory: " . $this->uploadDir);
            }
        }
        
        // التحقق من صلاحيات الكتابة
        if (!is_writable($this->uploadDir)) {
            error_log("Upload directory is not writable: " . $this->uploadDir);
        }
    }
    
    public function uploadImage($file) {
        try {
            // التحقق من وجود الملف
            if (!isset($file) || !isset($file['tmp_name'])) {
                throw new Exception('لم يتم اختيار ملف');
            }
            
            // التحقق من عدم وجود أخطاء في الرفع
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('فشل رفع الملف. الخطأ: ' . $file['error']);
            }
            
            // التحقق من حجم الملف
            if ($file['size'] > $this->maxFileSize) {
                throw new Exception('حجم الصورة كبير جداً. الحد الأقصى 5 ميجا');
            }
            
            // التحقق من نوع الملف
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $this->allowedTypes)) {
                throw new Exception('نوع الملف غير مسموح. استخدم JPG أو PNG فقط');
            }
            
            // التحقق من أن الملف صورة فعلاً
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                throw new Exception('الملف ليس صورة صحيحة');
            }
            
            // إنشاء اسم فريد للملف
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('property_', true) . '_' . time() . '.' . $extension;
            $filepath = $this->uploadDir . $filename;
            
            // معالجة وحفظ الصورة
            if (!$this->processImage($file['tmp_name'], $filepath, $mimeType)) {
                throw new Exception('فشل معالجة الصورة');
            }
            
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filepath
            ];
            
        } catch (Exception $e) {
            error_log("Image Upload Error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function uploadMultipleImages($files, $maxImages = 10) {
        $results = [];
        
        // التحقق من وجود الملفات
        if (!isset($files['name']) || !is_array($files['name'])) {
            error_log("No files array found");
            return $results;
        }
        
        $count = min(count($files['name']), $maxImages);
        
        for ($i = 0; $i < $count; $i++) {
            // تخطي الملفات الفارغة
            if (empty($files['name'][$i]) || $files['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            
            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];
            
            $result = $this->uploadImage($file);
            
            if ($result['success']) {
                $results[] = $result;
            } else {
                error_log("Failed to upload image {$i}: " . ($result['error'] ?? 'Unknown error'));
            }
        }
        
        return $results;
    }
    
    private function processImage($sourcePath, $destinationPath, $mimeType) {
        try {
            // إنشاء صورة من المصدر
            switch ($mimeType) {
                case 'image/jpeg':
                case 'image/jpg':
                    $image = @imagecreatefromjpeg($sourcePath);
                    break;
                case 'image/png':
                    $image = @imagecreatefrompng($sourcePath);
                    break;
                default:
                    error_log("Unsupported image type: " . $mimeType);
                    return false;
            }
            
            if ($image === false) {
                error_log("Failed to create image from source");
                return false;
            }
            
            // الحصول على أبعاد الصورة
            list($width, $height) = getimagesize($sourcePath);
            
            $maxWidth = 1920;
            $maxHeight = 1080;
            
            // حساب نسبة التصغير
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            
            // تصغير الصورة إذا لزم الأمر
            if ($ratio < 1) {
                $newWidth = (int)($width * $ratio);
                $newHeight = (int)($height * $ratio);
                
                $newImage = imagecreatetruecolor($newWidth, $newHeight);
                
                // الحفاظ على الشفافية للـ PNG
                if ($mimeType === 'image/png') {
                    imagealphablending($newImage, false);
                    imagesavealpha($newImage, true);
                }
                
                imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                
                $success = imagejpeg($newImage, $destinationPath, 85);
                imagedestroy($newImage);
            } else {
                // حفظ الصورة بدون تصغير
                $success = imagejpeg($image, $destinationPath, 85);
            }
            
            imagedestroy($image);
            
            if (!$success) {
                error_log("Failed to save image to: " . $destinationPath);
                return false;
            }
            
            // التحقق من أن الملف تم حفظه فعلاً
            if (!file_exists($destinationPath)) {
                error_log("Image file does not exist after save: " . $destinationPath);
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Image processing error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * حذف صورة
     */
    public function deleteImage($filepath) {
        if (file_exists($filepath)) {
            return @unlink($filepath);
        }
        return false;
    }
}
?>