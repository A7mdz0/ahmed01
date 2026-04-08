<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// جلب المناطق
$locations = [];
$stmt = $pdo->query("SELECT location_id, city, district FROM locations ORDER BY city, district");
while ($row = $stmt->fetch()) {
    $locations[$row['city']][] = $row;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة عقار جديد - دار السودان</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 2rem 0; direction: rtl; }
        .container { max-width: 900px; margin: 0 auto; padding: 0 1rem; }
        .header { background: white; padding: 1.5rem 2rem; border-radius: 15px; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .header h1 { color: #333; font-size: 1.8rem; margin-bottom: 0.5rem; }
        .header p { color: #666; }
        .form-card { background: white; padding: 2.5rem; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 2rem; }
        .section-title { font-size: 1.3rem; color: #333; margin-bottom: 1.5rem; padding-bottom: 0.5rem; border-bottom: 2px solid #667eea; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 600; }
        .required { color: #ef4444; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.9rem; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1rem; transition: all 0.3s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
        .form-group textarea { min-height: 120px; resize: vertical; font-family: inherit; }
        .image-upload-area { border: 3px dashed #667eea; border-radius: 15px; padding: 3rem 2rem; text-align: center; cursor: pointer; transition: all 0.3s; background: rgba(102, 126, 234, 0.02); }
        .image-upload-area:hover { background: rgba(102, 126, 234, 0.08); }
        .image-upload-area input[type="file"] { display: none; }
        .upload-icon { font-size: 3rem; margin-bottom: 1rem; }
        .upload-text { font-size: 1.1rem; color: #333; margin-bottom: 0.5rem; }
        .upload-hint { color: #999; font-size: 0.9rem; }
        .image-preview { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1rem; margin-top: 1.5rem; }
        .preview-item { position: relative; border-radius: 10px; overflow: hidden; box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
        .preview-item img { width: 100%; height: 150px; object-fit: cover; }
        .remove-image { position: absolute; top: 5px; left: 5px; background: #ef4444; color: white; border: none; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 1.2rem; line-height: 1; }
        .btn-submit { width: 100%; padding: 1.2rem; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 12px; font-size: 1.2rem; font-weight: bold; cursor: pointer; transition: all 0.3s; }
        .btn-submit:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3); }
        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }
        .alert { padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; display: none; }
        .alert-error { background: #fee; color: #c33; border: 1px solid #fcc; }
        .alert-success { background: #efe; color: #3c3; border: 1px solid #cfc; }
        .back-btn { display: inline-block; background: white; color: #667eea; padding: 0.7rem 1.5rem; border-radius: 10px; text-decoration: none; font-weight: 600; margin-bottom: 1rem; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    
    <div class="container">
        <a href="index.php" class="back-btn">← العودة للرئيسية</a>
        
        <div class="header">
            <h1>🏡 إضافة عقار جديد</h1>
            <p>املأ البيانات بدقة لضمان سرعة الموافقة على إعلانك</p>
        </div>
        
        <div id="alert" class="alert"></div>
        
        <form id="propertyForm" enctype="multipart/form-data">
            
            <div class="form-card">
                <h2 class="section-title">📋 المعلومات الأساسية</h2>
                
                <div class="form-group">
                    <label>عنوان الإعلان <span class="required">*</span></label>
                    <input type="text" name="title" placeholder="مثال: فيلا فاخرة في الرياض" required>
                </div>
                
                <div class="form-group">
                    <label>وصف تفصيلي للعقار <span class="required">*</span></label>
                    <textarea name="description" placeholder="اكتب وصفاً شاملاً للعقار، المميزات، القرب من الخدمات..." required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>نوع العقار <span class="required">*</span></label>
                        <select name="property_type" required>
                            <option value="">اختر نوع العقار</option>
                            <option value="شقة">شقة</option>
                            <option value="فيلا">فيلا</option>
                            <option value="منزل">منزل</option>
                            <option value="أرض">أرض</option>
                            <option value="محل تجاري">محل تجاري</option>
                            <option value="مكتب">مكتب</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>نوع الإعلان <span class="required">*</span></label>
                        <select name="listing_type" required>
                            <option value="">اختر النوع</option>
                            <option value="للبيع">للبيع</option>
                            <option value="للإيجار">للإيجار</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>السعر (جنيه سوداني) <span class="required">*</span></label>
                        <input type="number" name="price" placeholder="مثال: 45000000" required>
                    </div>
                    
                    <div class="form-group">
                        <label>المساحة (متر مربع) <span class="required">*</span></label>
                        <input type="number" name="area" placeholder="مثال: 350" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>عدد غرف النوم</label>
                        <input type="number" name="bedrooms" placeholder="مثال: 4">
                    </div>
                    
                    <div class="form-group">
                        <label>عدد دورات المياه</label>
                        <input type="number" name="bathrooms" placeholder="مثال: 3">
                    </div>
                </div>
            </div>
            
            <div class="form-card">
                <h2 class="section-title">📍 الموقع</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>المدينة <span class="required">*</span></label>
                        <select name="city" id="citySelect" required>
                            <option value="">اختر المدينة</option>
                            <?php foreach (array_keys($locations) as $city): ?>
                                <option value="<?= htmlspecialchars($city) ?>"><?= htmlspecialchars($city) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>الحي/المنطقة <span class="required">*</span></label>
                        <select name="location_id" id="districtSelect" required>
                            <option value="">اختر الحي</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>العنوان التفصيلي <span class="required">*</span></label>
                    <input type="text" name="address" placeholder="مثال: شارع الستين، بالقرب من مسجد النور" required>
                </div>
            </div>
            
            <div class="form-card">
                <h2 class="section-title">📸 صور العقار</h2>
                
                <label for="imageInput" class="image-upload-area">
                    <div class="upload-icon">📁</div>
                    <div class="upload-text">اضغط لإضافة الصور</div>
                    <div class="upload-hint">يمكنك إضافة حتى 10 صور (JPG, PNG) - الحد الأقصى 5 ميجا للصورة</div>
                    <input type="file" id="imageInput" name="images[]" multiple accept="image/jpeg,image/jpg,image/png">
                </label>
                
                <div id="imagePreview" class="image-preview"></div>
                <div id="imageCount" style="margin-top: 1rem; color: #667eea; font-weight: 600; text-align: center;"></div>
            </div>
            
            <button type="submit" class="btn-submit" id="submitBtn">نشر الإعلان</button>
        </form>
    </div>
    
    <script>
        const locations = <?= json_encode($locations) ?>;
        
        // تحديث المناطق عند اختيار المدينة
        document.getElementById('citySelect').addEventListener('change', function() {
            const city = this.value;
            const districtSelect = document.getElementById('districtSelect');
            
            districtSelect.innerHTML = '<option value="">اختر الحي</option>';
            
            if (city && locations[city]) {
                locations[city].forEach(loc => {
                    const option = document.createElement('option');
                    option.value = loc.location_id;
                    option.textContent = loc.district;
                    districtSelect.appendChild(option);
                });
            }
        });
        
        let selectedFiles = [];
        
        // إضافة الصور
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            
            files.forEach(file => {
                if (selectedFiles.length >= 10) {
                    alert('يمكنك إضافة 10 صور كحد أقصى');
                    return;
                }
                
                if (file.size > 5 * 1024 * 1024) {
                    alert('حجم الصورة يجب أن يكون أقل من 5 ميجا: ' + file.name);
                    return;
                }
                
                if (!file.type.match('image/(jpeg|jpg|png)')) {
                    alert('نوع الملف غير مدعوم. استخدم JPG أو PNG فقط: ' + file.name);
                    return;
                }
                
                selectedFiles.push(file);
            });
            
            updatePreview();
            this.value = ''; // إعادة تعيين input
        });
        
        // حذف صورة
        function removeImage(index) {
            selectedFiles.splice(index, 1);
            updatePreview();
        }
        
        // تحديث معاينة الصور
        function updatePreview() {
            const preview = document.getElementById('imagePreview');
            const countDiv = document.getElementById('imageCount');
            preview.innerHTML = '';
            
            if (selectedFiles.length === 0) {
                countDiv.textContent = '';
            } else {
                countDiv.textContent = `✓ تم اختيار ${selectedFiles.length} صورة`;
            }
            
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="معاينة ${index + 1}">
                        <button type="button" class="remove-image" onclick="removeImage(${index})">×</button>
                    `;
                    preview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }
        
        // إرسال النموذج
        document.getElementById('propertyForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const alert = document.getElementById('alert');
            const submitBtn = document.getElementById('submitBtn');
            
            // التحقق من الصور
            if (selectedFiles.length === 0) {
                alert.className = 'alert alert-error';
                alert.textContent = '✗ يجب إضافة صورة واحدة على الأقل';
                alert.style.display = 'block';
                window.scrollTo(0, 0);
                return;
            }
            
            // إنشاء FormData وإضافة جميع الحقول
            const formData = new FormData(this);
            
            // حذف الصور القديمة من FormData
            formData.delete('images[]');
            
            // إضافة الصور المختارة
            selectedFiles.forEach((file, index) => {
                formData.append('images[]', file, file.name);
            });
            
            // تعطيل الزر وإظهار رسالة التحميل
            submitBtn.disabled = true;
            submitBtn.textContent = 'جاري النشر...';
            
            alert.className = 'alert alert-success';
            alert.textContent = 'جاري إضافة العقار...';
            alert.style.display = 'block';
            window.scrollTo(0, 0);
            
            try {
                const response = await fetch('api/add_property.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert.className = 'alert alert-success';
                    alert.textContent = `✓ تم إضافة العقار بنجاح! تم رفع ${data.images_uploaded} صورة. سيتم مراجعته من قبل الإدارة.`;
                    
                    // إعادة تعيين النموذج
                    this.reset();
                    selectedFiles = [];
                    updatePreview();
                    
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    alert.className = 'alert alert-error';
                    alert.textContent = '✗ ' + data.message;
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'نشر الإعلان';
                }
            } catch (error) {
                console.error('Error:', error);
                alert.className = 'alert alert-error';
                alert.textContent = '✗ حدث خطأ. يرجى المحاولة مرة أخرى.';
                submitBtn.disabled = false;
                submitBtn.textContent = 'نشر الإعلان';
            }
        });
    </script>
    
</body>
</html>