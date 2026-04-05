<?php
/**
 * owner/add_field.php
 * إضافة ملعب جديد — مع رابط الخريطة + رفع أكثر من صورة
 */
session_start();
require_once '../auth/check_auth.php';
require_user_type('owner');
require_once '../includes/db.php';
require_once '../includes/functions.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── جمع البيانات ────────────────────────────────────
    $field_name       = clean_input($_POST['field_name']);
    $city             = clean_input($_POST['city']);
    $address          = clean_input($_POST['address']);
    $map_link         = clean_input($_POST['map_link'] ?? '');
    $description      = clean_input($_POST['description']);
    $price_per_hour   = floatval($_POST['price_per_hour']);
    $field_type       = clean_input($_POST['field_type']);
    $has_lighting     = isset($_POST['has_lighting'])      ? 1 : 0;
    $has_parking      = isset($_POST['has_parking'])       ? 1 : 0;
    $has_changing_rooms = isset($_POST['has_changing_rooms']) ? 1 : 0;
    $opening_time     = clean_input($_POST['opening_time']);
    $closing_time     = clean_input($_POST['closing_time']);

    // ── التحقق من رابط الخريطة ──────────────────────────
    if (!empty($map_link) && !filter_var($map_link, FILTER_VALIDATE_URL)) {
        $error = 'رابط الخريطة غير صحيح، يجب أن يبدأ بـ https://';
    }

    // ── رفع الصور المتعددة ──────────────────────────────
    $uploaded_images = [];

    if (empty($error) && isset($_FILES['field_images']) && !empty($_FILES['field_images']['name'][0])) {
        $files      = $_FILES['field_images'];
        $file_count = count($files['name']);

        if ($file_count > 8) {
            $error = 'الحد الأقصى 8 صور للملعب';
        } else {
            for ($i = 0; $i < $file_count; $i++) {
                if ($files['error'][$i] !== 0) continue;

                // تحويل الملف المفرد لصيغة upload_field_image
                $single_file = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                ];

                $result = upload_field_image($single_file);
                if ($result['success']) {
                    $uploaded_images[] = $result['filename'];
                } else {
                    $error = 'خطأ في الصورة ' . ($i + 1) . ': ' . $result['message'];
                    break;
                }
            }
        }
    }

    // ── حفظ الملعب في قاعدة البيانات ───────────────────
    if (empty($error)) {

        // الصورة الرئيسية = أول صورة مرفوعة (أو null)
        $main_image = !empty($uploaded_images) ? $uploaded_images[0] : null;

        $sql  = "INSERT INTO fields
                    (owner_id, field_name, city, address, map_link, description,
                     price_per_hour, field_type, has_lighting, has_parking,
                     has_changing_rooms, image_path, opening_time, closing_time)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isssssdsiiisss",
            $_SESSION['user_id'], $field_name, $city, $address, $map_link,
            $description, $price_per_hour, $field_type,
            $has_lighting, $has_parking, $has_changing_rooms,
            $main_image, $opening_time, $closing_time
        );

        if ($stmt->execute()) {
            $field_id = $conn->insert_id;

            // حفظ الصور في جدول field_images
            if (!empty($uploaded_images)) {
                $img_sql  = "INSERT INTO field_images (field_id, image_path, is_primary, sort_order) VALUES (?, ?, ?, ?)";
                $img_stmt = $conn->prepare($img_sql);
                foreach ($uploaded_images as $idx => $img) {
                    $is_primary = ($idx === 0) ? 1 : 0;
                    $img_stmt->bind_param("isii", $field_id, $img, $is_primary, $idx);
                    $img_stmt->execute();
                }
            }

            $_SESSION['success_message'] = '✅ تمت إضافة الملعب بنجاح!';
            redirect('my_fields.php');
        } else {
            $error = 'حدث خطأ أثناء الحفظ: ' . $conn->error;
        }
    }
}

$page_title = 'إضافة ملعب جديد';
include '../includes/header.php';
?>

<div class="container my-5">
    <div class="page-header">
        <h2><i class="fas fa-plus-circle"></i> إضافة ملعب جديد</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="row">

            <!-- ── العمود الأيمن ──────────────────────── -->
            <div class="col-md-8">

                <!-- بيانات أساسية -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-4" style="color:var(--accent);">
                            <i class="fas fa-info-circle"></i> المعلومات الأساسية
                        </h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>🏟️ اسم الملعب <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="field_name"
                                           placeholder="مثال: ملعب الأبطال" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>🏙️ المدينة <span class="text-danger">*</span></label>
                                    <select class="form-control" name="city" required>
                                        <option value="الخرطوم">الخرطوم</option>
                                        <option value="الخرطوم بحري">الخرطوم بحري</option>
                                        <option value="أم درمان">أم درمان</option>
                                        <option value="بورتسودان">بورتسودان</option>
                                        <option value="عطبرة">عطبرة</option>
                                        <option value="ود مدني">ود مدني</option>
                                        <option value="كسلا">كسلا</option>
                                        <option value="الأبيض">الأبيض</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>📍 العنوان التفصيلي <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="address"
                                   placeholder="مثال: حي الرياض، شارع الستين، بجانب المسجد" required>
                        </div>

                        <!-- رابط الخريطة -->
                        <div class="form-group">
                            <label>
                                🗺️ رابط الموقع على Google Maps
                                <small class="text-muted">(اختياري)</small>
                            </label>
                            <input type="url" class="form-control" name="map_link"
                                   id="map_link"
                                   placeholder="https://maps.google.com/?q=...">

                            <!-- شرح كيفية الحصول على الرابط -->
                            <div class="mt-2 p-3"
                                 style="background:var(--accent-light);border-radius:10px;
                                        border-right:4px solid var(--accent);">
                                <p class="mb-1" style="font-size:13px;font-weight:bold;color:var(--accent);">
                                    <i class="fas fa-lightbulb"></i> كيف تحصل على رابط الموقع؟
                                </p>
                                <ol style="font-size:12px;color:var(--text-secondary);margin:0;padding-right:16px;">
                                    <li>افتح <a href="https://maps.google.com" target="_blank">Google Maps</a> على الجوال أو الكمبيوتر</li>
                                    <li>ابحث عن موقع ملعبك أو حدده على الخريطة</li>
                                    <li>اضغط على المكان ← اضغط <strong>مشاركة</strong></li>
                                    <li>اختر <strong>نسخ الرابط</strong> والصقه هنا</li>
                                </ol>
                            </div>

                            <!-- معاينة الرابط -->
                            <div id="mapPreview" style="display:none;margin-top:10px;">
                                <a id="mapPreviewLink" href="#" target="_blank"
                                   class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-map-marker-alt"></i>
                                    معاينة الموقع على الخريطة
                                </a>
                                <span class="text-success mr-2" style="font-size:13px;">
                                    ✅ رابط صحيح
                                </span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>📝 وصف الملعب</label>
                            <textarea class="form-control" name="description" rows="4"
                                      placeholder="اكتب وصفاً مميزاً للملعب — نوع العشب، المميزات، الأجواء..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- التفاصيل والأوقات -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="mb-4" style="color:var(--accent);">
                            <i class="fas fa-sliders-h"></i> التفاصيل والأوقات
                        </h5>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>💰 السعر / ساعة (ج.س) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="price_per_hour"
                                           step="0.01" min="1" placeholder="150" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>⚽ نوع الملعب <span class="text-danger">*</span></label>
                                    <select class="form-control" name="field_type" required>
                                        <option value="خماسي">خماسي (5×5)</option>
                                        <option value="سباعي">سباعي (7×7)</option>
                                        <option value="تساعي">تساعي (9×9)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>🏷️ حالة الملعب</label>
                                    <select class="form-control" name="is_active">
                                        <option value="1">✅ نشط ومتاح</option>
                                        <option value="0">⏸️ موقف مؤقتاً</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>🕗 وقت الافتتاح <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" name="opening_time" value="08:00" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>🕙 وقت الإغلاق <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" name="closing_time" value="23:00" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold mb-3">✨ المميزات المتوفرة:</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input"
                                               id="has_lighting" name="has_lighting">
                                        <label class="custom-control-label" for="has_lighting">
                                            💡 إضاءة ليلية
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input"
                                               id="has_parking" name="has_parking">
                                        <label class="custom-control-label" for="has_parking">
                                            🅿️ موقف سيارات
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input"
                                               id="has_changing_rooms" name="has_changing_rooms">
                                        <label class="custom-control-label" for="has_changing_rooms">
                                            🚪 غرف تغيير
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ── العمود الأيسر: رفع الصور ─────────── -->
            <div class="col-md-4">
                <div class="card mb-4" style="position:sticky;top:20px;">
                    <div class="card-body">
                        <h5 class="mb-3" style="color:var(--accent);">
                            <i class="fas fa-images"></i> صور الملعب
                        </h5>

                        <!-- منطقة رفع الصور -->
                        <div id="dropZone"
                             onclick="document.getElementById('field_images').click()"
                             style="border:2px dashed var(--accent);border-radius:12px;
                                    padding:30px 20px;text-align:center;cursor:pointer;
                                    background:var(--accent-light);transition:all .3s;">
                            <i class="fas fa-cloud-upload-alt fa-3x mb-2"
                               style="color:var(--accent);"></i>
                            <p class="mb-1" style="color:var(--accent);font-weight:bold;">
                                اضغط لاختيار الصور
                            </p>
                            <small style="color:var(--text-muted);">
                                JPG, PNG — حد أقصى 8 صور — 5MB لكل صورة
                            </small>
                        </div>

                        <!-- Input مخفي للصور المتعددة -->
                        <input type="file" id="field_images" name="field_images[]"
                               multiple accept="image/*"
                               style="display:none;"
                               onchange="previewImages(this)">

                        <!-- معاينة الصور المختارة -->
                        <div id="imagePreviewGrid"
                             class="mt-3"
                             style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                        </div>

                        <!-- عداد الصور -->
                        <div id="imageCount" class="mt-2 text-center"
                             style="font-size:13px;color:var(--text-muted);display:none;">
                        </div>

                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle"></i>
                            أول صورة ستكون الصورة الرئيسية للملعب
                        </small>
                    </div>
                </div>
            </div>

        </div><!-- /row -->

        <!-- أزرار الحفظ -->
        <div class="text-center mt-2 mb-5">
            <button type="submit" class="btn btn-success btn-lg px-5">
                <i class="fas fa-check"></i> إضافة الملعب
            </button>
            <a href="my_fields.php" class="btn btn-secondary btn-lg px-4 mr-2">
                <i class="fas fa-times"></i> إلغاء
            </a>
        </div>

    </form>
</div>

<script>
// ── معاينة الصور قبل الرفع ────────────────────────────
function previewImages(input) {
    var grid  = document.getElementById('imagePreviewGrid');
    var count = document.getElementById('imageCount');
    grid.innerHTML = '';

    var files = Array.from(input.files);

    if (files.length === 0) { count.style.display = 'none'; return; }
    if (files.length > 8) {
        alert('الحد الأقصى 8 صور');
        input.value = '';
        count.style.display = 'none';
        return;
    }

    files.forEach(function (file, idx) {
        var reader = new FileReader();
        reader.onload = function (e) {
            var wrapper = document.createElement('div');
            wrapper.style.cssText = 'position:relative;border-radius:8px;overflow:hidden;aspect-ratio:1;';

            var img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;';

            // شارة "رئيسية" على أول صورة
            if (idx === 0) {
                var badge = document.createElement('div');
                badge.textContent = '★ رئيسية';
                badge.style.cssText = 'position:absolute;bottom:0;left:0;right:0;'
                    + 'background:rgba(22,163,74,.85);color:#fff;font-size:10px;'
                    + 'text-align:center;padding:2px;font-weight:bold;';
                wrapper.appendChild(badge);
            }

            wrapper.appendChild(img);
            grid.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    });

    count.textContent = files.length + ' صورة مختارة';
    count.style.display = 'block';
}

// ── معاينة رابط الخريطة ──────────────────────────────
document.getElementById('map_link').addEventListener('input', function () {
    var val     = this.value.trim();
    var preview = document.getElementById('mapPreview');
    var link    = document.getElementById('mapPreviewLink');

    if (val && val.startsWith('http')) {
        link.href = val;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
});

// ── Drag & Drop للصور ────────────────────────────────
var dropZone = document.getElementById('dropZone');

dropZone.addEventListener('dragover', function (e) {
    e.preventDefault();
    this.style.background = 'var(--accent)';
    this.style.color      = '#fff';
});
dropZone.addEventListener('dragleave', function () {
    this.style.background = 'var(--accent-light)';
    this.style.color      = '';
});
dropZone.addEventListener('drop', function (e) {
    e.preventDefault();
    this.style.background = 'var(--accent-light)';
    var input = document.getElementById('field_images');
    input.files = e.dataTransfer.files;
    previewImages(input);
});
</script>

<?php include '../includes/footer.php'; ?>