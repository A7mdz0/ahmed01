<?php
/**
 * اختبار نظام رفع الصور
 */
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار رفع الصور</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
            direction: rtl;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 2rem;
            text-align: center;
        }
        .info-box {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .success { background: #d1fae5; border: 2px solid #10b981; }
        .error { background: #fee2e2; border: 2px solid #ef4444; }
        .warning { background: #fef3c7; border: 2px solid #f59e0b; }
        .status {
            font-weight: bold;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            color: white;
        }
        .status.ok { background: #10b981; }
        .status.fail { background: #ef4444; }
        .status.warn { background: #f59e0b; }
        code {
            background: #f3f4f6;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-family: monospace;
        }
        .section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 10px;
        }
        h2 {
            color: #667eea;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 اختبار نظام رفع الصور</h1>

        <!-- فحص إعدادات PHP -->
        <div class="section">
            <h2>📋 إعدادات PHP</h2>
            
            <?php
            $upload_max = ini_get('upload_max_filesize');
            $post_max = ini_get('post_max_size');
            $memory_limit = ini_get('memory_limit');
            $max_file_uploads = ini_get('max_file_uploads');
            
            echo "<div class='info-box " . (intval($upload_max) >= 5 ? 'success' : 'warning') . "'>
                <div><strong>upload_max_filesize:</strong> <code>$upload_max</code></div>
                <span class='status " . (intval($upload_max) >= 5 ? 'ok' : 'warn') . "'>" . (intval($upload_max) >= 5 ? 'جيد' : 'منخفض') . "</span>
            </div>";
            
            echo "<div class='info-box " . (intval($post_max) >= 10 ? 'success' : 'warning') . "'>
                <div><strong>post_max_size:</strong> <code>$post_max</code></div>
                <span class='status " . (intval($post_max) >= 10 ? 'ok' : 'warn') . "'>" . (intval($post_max) >= 10 ? 'جيد' : 'منخفض') . "</span>
            </div>";
            
            echo "<div class='info-box " . (intval($memory_limit) >= 128 ? 'success' : 'warning') . "'>
                <div><strong>memory_limit:</strong> <code>$memory_limit</code></div>
                <span class='status " . (intval($memory_limit) >= 128 ? 'ok' : 'warn') . "'>" . (intval($memory_limit) >= 128 ? 'جيد' : 'منخفض') . "</span>
            </div>";
            
            echo "<div class='info-box success'>
                <div><strong>max_file_uploads:</strong> <code>$max_file_uploads</code></div>
                <span class='status ok'>جيد</span>
            </div>";
            ?>
        </div>

        <!-- فحص امتدادات PHP -->
        <div class="section">
            <h2>🔧 امتدادات PHP المطلوبة</h2>
            
            <?php
            $extensions = [
                'gd' => 'معالجة الصور',
                'fileinfo' => 'معلومات الملفات',
                'pdo' => 'قاعدة البيانات',
                'pdo_mysql' => 'MySQL'
            ];
            
            foreach ($extensions as $ext => $desc) {
                $loaded = extension_loaded($ext);
                echo "<div class='info-box " . ($loaded ? 'success' : 'error') . "'>
                    <div><strong>$ext:</strong> $desc</div>
                    <span class='status " . ($loaded ? 'ok' : 'fail') . "'>" . ($loaded ? 'مثبت' : 'غير مثبت') . "</span>
                </div>";
            }
            ?>
        </div>

        <!-- فحص المجلدات -->
        <div class="section">
            <h2>📁 المجلدات والصلاحيات</h2>
            
            <?php
            $directories = [
                'uploads' => 'مجلد الرفع الرئيسي',
                'uploads/properties' => 'مجلد صور العقارات'
            ];
            
            foreach ($directories as $dir => $desc) {
                $exists = file_exists($dir);
                $writable = $exists && is_writable($dir);
                $permissions = $exists ? substr(sprintf('%o', fileperms($dir)), -4) : 'N/A';
                
                if (!$exists) {
                    echo "<div class='info-box error'>
                        <div><strong>$dir:</strong> $desc<br><small>المجلد غير موجود</small></div>
                        <span class='status fail'>غير موجود</span>
                    </div>";
                    
                    if (@mkdir($dir, 0755, true)) {
                        echo "<div class='info-box success'>
                            <div><strong>✓ تم إنشاء:</strong> $dir</div>
                            <span class='status ok'>تم</span>
                        </div>";
                    }
                } elseif (!$writable) {
                    echo "<div class='info-box warning'>
                        <div><strong>$dir:</strong> $desc<br><small>الصلاحيات: $permissions (غير قابل للكتابة)</small></div>
                        <span class='status warn'>يحتاج تعديل</span>
                    </div>";
                } else {
                    echo "<div class='info-box success'>
                        <div><strong>$dir:</strong> $desc<br><small>الصلاحيات: $permissions</small></div>
                        <span class='status ok'>جاهز</span>
                    </div>";
                }
            }
            ?>
        </div>

        <!-- التوصيات -->
        <div class="section">
            <h2>💡 التوصيات</h2>
            
            <div style="padding: 1rem; background: white; border-radius: 8px; margin-bottom: 1rem;">
                <h3 style="color: #333; margin-bottom: 0.5rem;">إذا كانت الإعدادات منخفضة:</h3>
                <p style="margin-bottom: 0.5rem;"><strong>1. عدّل ملف php.ini:</strong></p>
                <code style="display: block; padding: 0.5rem; margin-bottom: 0.5rem;">
                    upload_max_filesize = 10M<br>
                    post_max_size = 20M<br>
                    memory_limit = 256M<br>
                    max_file_uploads = 20
                </code>
                <p style="margin-top: 1rem;"><strong>2. أعد تشغيل Apache/Nginx</strong></p>
            </div>
            
            <div style="padding: 1rem; background: white; border-radius: 8px;">
                <h3 style="color: #333; margin-bottom: 0.5rem;">لتعديل صلاحيات المجلدات:</h3>
                <p style="margin-bottom: 0.5rem;"><strong>على Linux/Mac:</strong></p>
                <code style="display: block; padding: 0.5rem;">
                    chmod -R 755 uploads/<br>
                    chown -R www-data:www-data uploads/
                </code>
            </div>
        </div>

        <!-- اختبار قاعدة البيانات -->
        <div class="section">
            <h2>🗄️ قاعدة البيانات</h2>
            
            <?php
            try {
                require_once 'config/database.php';
                
                // اختبار الاتصال
                $pdo->query("SELECT 1");
                echo "<div class='info-box success'>
                    <div><strong>الاتصال بقاعدة البيانات:</strong> متصل بنجاح</div>
                    <span class='status ok'>متصل</span>
                </div>";
                
                // فحص الجداول
                $tables = ['users', 'properties', 'property_images', 'locations'];
                foreach ($tables as $table) {
                    $result = $pdo->query("SHOW TABLES LIKE '$table'");
                    $exists = $result->rowCount() > 0;
                    
                    echo "<div class='info-box " . ($exists ? 'success' : 'error') . "'>
                        <div><strong>جدول $table:</strong></div>
                        <span class='status " . ($exists ? 'ok' : 'fail') . "'>" . ($exists ? 'موجود' : 'غير موجود') . "</span>
                    </div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='info-box error'>
                    <div><strong>خطأ في الاتصال:</strong> " . htmlspecialchars($e->getMessage()) . "</div>
                    <span class='status fail'>فشل</span>
                </div>";
            }
            ?>
        </div>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="add_property.php" style="display: inline-block; background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 1rem 2rem; border-radius: 10px; text-decoration: none; font-weight: 600;">
                جرب إضافة عقار الآن
            </a>
        </div>
    </div>
</body>
</html>