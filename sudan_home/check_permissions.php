<?php
/**
 * فحص صلاحيات المجلدات والملفات
 */

$directories = [
    'uploads',
    'uploads/properties',
];

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>فحص صلاحيات النظام</title>
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
            max-width: 800px;
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
            font-size: 2rem;
        }
        .check-item {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .success {
            background: #d1fae5;
            border: 2px solid #10b981;
        }
        .error {
            background: #fee2e2;
            border: 2px solid #ef4444;
        }
        .warning {
            background: #fef3c7;
            border: 2px solid #f59e0b;
        }
        .status {
            font-weight: bold;
            padding: 0.5rem 1rem;
            border-radius: 20px;
        }
        .status.ok {
            background: #10b981;
            color: white;
        }
        .status.fail {
            background: #ef4444;
            color: white;
        }
        .status.warn {
            background: #f59e0b;
            color: white;
        }
        .fix-button {
            background: #667eea;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 1rem;
        }
        .fix-button:hover {
            background: #5568d3;
        }
        .info-box {
            background: #e0e7ff;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 2rem;
            border: 2px solid #667eea;
        }
        .info-box h3 {
            color: #667eea;
            margin-bottom: 1rem;
        }
        .info-box code {
            background: white;
            padding: 0.3rem 0.6rem;
            border-radius: 5px;
            font-family: monospace;
            color: #333;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 فحص صلاحيات النظام</h1>";

// فحص المجلدات
foreach ($directories as $dir) {
    $exists = file_exists($dir);
    $writable = $exists ? is_writable($dir) : false;
    $permissions = $exists ? substr(sprintf('%o', fileperms($dir)), -4) : 'N/A';
    
    if (!$exists) {
        echo "<div class='check-item error'>
            <div>
                <strong>❌ المجلد: $dir</strong><br>
                <small>المجلد غير موجود</small>
            </div>
            <span class='status fail'>غير موجود</span>
        </div>";
        
        // محاولة إنشاء المجلد
        if (@mkdir($dir, 0755, true)) {
            echo "<div class='check-item success'>
                <div>
                    <strong>✅ تم إنشاء المجلد: $dir</strong>
                </div>
            </div>";
        }
    } elseif (!$writable) {
        echo "<div class='check-item warning'>
            <div>
                <strong>⚠️ المجلد: $dir</strong><br>
                <small>الصلاحيات الحالية: $permissions (غير قابل للكتابة)</small>
            </div>
            <span class='status warn'>يحتاج تعديل</span>
        </div>";
    } else {
        echo "<div class='check-item success'>
            <div>
                <strong>✅ المجلد: $dir</strong><br>
                <small>الصلاحيات: $permissions (قابل للكتابة)</small>
            </div>
            <span class='status ok'>جاهز</span>
        </div>";
    }
}

// فحص قاعدة البيانات
try {
    require_once 'config/database.php';
    echo "<div class='check-item success'>
        <div>
            <strong>✅ الاتصال بقاعدة البيانات</strong><br>
            <small>تم الاتصال بنجاح</small>
        </div>
        <span class='status ok'>متصل</span>
    </div>";
} catch (Exception $e) {
    echo "<div class='check-item error'>
        <div>
            <strong>❌ الاتصال بقاعدة البيانات</strong><br>
            <small>خطأ: " . htmlspecialchars($e->getMessage()) . "</small>
        </div>
        <span class='status fail'>فشل</span>
    </div>";
}

// معلومات إضافية
echo "<div class='info-box'>
    <h3>📝 كيفية تعديل الصلاحيات</h3>
    <p style='margin-bottom: 1rem;'><strong>على Windows:</strong></p>
    <p style='margin-bottom: 0.5rem;'>1. انقر بزر الفأرة الأيمن على المجلد</p>
    <p style='margin-bottom: 0.5rem;'>2. اختر Properties → Security</p>
    <p style='margin-bottom: 1.5rem;'>3. تأكد من أن المستخدم لديه صلاحيات Full Control</p>
    
    <p style='margin-bottom: 1rem;'><strong>على Linux/Mac:</strong></p>
    <p style='margin-bottom: 0.5rem;'>افتح Terminal واكتب:</p>
    <code>chmod -R 755 uploads/</code><br>
    <code style='margin-top: 0.5rem; display: inline-block;'>chown -R www-data:www-data uploads/</code>
</div>";

echo "</div></body></html>";
?>