<?php
/**
 * field_details.php — تفاصيل الملعب مع معرض الصور والخريطة
 *
 * ✅ إصلاح مشكلة "رفض الاتصال" في Google Maps
 *    السبب: maps.google.com/maps?output=embed  → محجوب
 *    الحل:  google.com/maps/embed?pb=          → يعمل ✅
 *           أو Leaflet (OpenStreetMap)          → بديل مجاني ✅
 */
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) redirect('index.php');

$field_id = intval($_GET['id']);

// بيانات الملعب
$sql = "SELECT f.*, u.full_name as owner_name, u.phone as owner_phone
        FROM fields f
        INNER JOIN users u ON f.owner_id = u.id
        WHERE f.id = ? AND f.is_active = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $field_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) redirect('index.php');
$field = $result->fetch_assoc();

// صور الملعب
$imgs_sql  = "SELECT * FROM field_images WHERE field_id = ? ORDER BY sort_order ASC";
$imgs_stmt = $conn->prepare($imgs_sql);
$imgs_stmt->bind_param("i", $field_id);
$imgs_stmt->execute();
$images = $imgs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($images) && !empty($field['image_path'])) {
    $images = [['image_path' => $field['image_path'], 'is_primary' => 1]];
}

// المراجعات
$rev_sql  = "SELECT r.*, u.full_name as customer_name FROM reviews r
             INNER JOIN users u ON r.customer_id = u.id
             WHERE r.field_id = ? ORDER BY r.created_at DESC LIMIT 5";
$rev_stmt = $conn->prepare($rev_sql);
$rev_stmt->bind_param("i", $field_id);
$rev_stmt->execute();
$reviews = $rev_stmt->get_result();

// ============================================================
// 🗺️  منطق الخريطة — مُصلَح
// ============================================================

// إحداثيات المدن السودانية (احتياطي)
$city_coords = [
    'الخرطوم'      => [15.5007, 32.5599],
    'الخرطوم بحري' => [15.6067, 32.5327],
    'أم درمان'     => [15.6444, 32.4777],
    'بورتسودان'    => [19.6158, 37.2164],
    'عطبرة'        => [17.7011, 33.9869],
    'ود مدني'      => [14.4000, 33.5196],
    'كسلا'         => [15.4560, 36.3994],
    'الأبيض'       => [13.1833, 30.2167],
];

$map_lat     = null;
$map_lng     = null;
$map_precise = false;   // هل الموقع دقيق؟

// 1) حاول استخراج الإحداثيات من رابط Google Maps
if (!empty($field['map_link'])) {
    $lnk = urldecode($field['map_link']);

    // أنماط شائعة لروابط Google Maps
    $patterns = [
        '/@(-?\d+\.?\d*),(-?\d+\.?\d*)/',          // .../maps/@lat,lng
        '/[?&]q=(-?\d+\.?\d*),(-?\d+\.?\d*)/',      // ?q=lat,lng
        '/!3d(-?\d+\.?\d+)!4d(-?\d+\.?\d+)/',       // !3d...!4d
        '/ll=(-?\d+\.?\d*),(-?\d+\.?\d*)/',          // ll=lat,lng
        '/center=(-?\d+\.?\d*),(-?\d+\.?\d*)/',      // center=lat,lng
        '/place\/[^@]+@(-?\d+\.?\d*),(-?\d+\.?\d*)/',// place/@lat,lng
    ];
    foreach ($patterns as $pat) {
        if (preg_match($pat, $lnk, $m)) {
            $map_lat     = (float)$m[1];
            $map_lng     = (float)$m[2];
            $map_precise = true;
            break;
        }
    }
}

// 2) إذا ما وجدنا إحداثيات — استخدم المدينة كاحتياطي
if (!$map_lat && isset($city_coords[$field['city']])) {
    $map_lat = $city_coords[$field['city']][0];
    $map_lng = $city_coords[$field['city']][1];
}

// إذا حتى المدينة مش موجودة — الخرطوم افتراضي
if (!$map_lat) { $map_lat = 15.5007; $map_lng = 32.5599; }

// -------------------------------------------------------
// ✅ روابط Embed الصحيحة
// -------------------------------------------------------

// رابط Google Maps لفتح في تبويب جديد
$open_link = "https://www.google.com/maps?q={$map_lat},{$map_lng}&z=17";
if (!$map_precise && !empty($field['map_link'])) {
    $open_link = $field['map_link'];
}

// -------------------------------------------------------
// طريقة الخريطة المستخدمة:
// نستخدم Leaflet (OpenStreetMap) كـ fallback موثوق 100%
// + زر Google Maps للفتح الخارجي
// -------------------------------------------------------

$page_title = $field['field_name'];
?>
<?php include 'includes/header.php'; ?>

<!-- Leaflet.js — مجاني، لا يحتاج API Key، لا يُحجب -->
<link  rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<style>
/* ════ Gallery ════════════════════════════════ */
.gallery-main-img {
    width:100%; height:420px; object-fit:cover;
    border-radius:14px; cursor:pointer;
    transition:transform .3s;
}
.gallery-main-img:hover { transform:scale(1.01); }
.gallery-thumb {
    width:100%; aspect-ratio:1; object-fit:cover;
    border-radius:8px; cursor:pointer;
    border:3px solid transparent;
    transition:all .3s;
}
.gallery-thumb:hover,
.gallery-thumb.active { border-color:var(--accent); transform:scale(1.05); }
.gallery-placeholder {
    width:100%; height:420px; border-radius:14px;
    background:linear-gradient(135deg,#16a34a,#15803d);
    display:flex; align-items:center; justify-content:center;
}

/* ════ Lightbox ═══════════════════════════════ */
#lightbox {
    display:none; position:fixed; inset:0; z-index:9999;
    background:rgba(0,0,0,.92);
    align-items:center; justify-content:center;
}
#lightbox.show { display:flex; }
#lightbox img  { max-width:90vw; max-height:90vh; border-radius:10px; }
.lb-btn {
    position:absolute; color:#fff; cursor:pointer;
    background:rgba(255,255,255,.12); border:none;
    border-radius:50%; display:flex; align-items:center;
    justify-content:center; transition:.3s;
}
.lb-btn:hover { background:rgba(255,255,255,.28); }
.close-lb { top:20px; left:20px;  width:44px; height:44px; font-size:22px; }
.prev-lb  { top:50%;  right:20px; width:50px; height:50px; font-size:26px; transform:translateY(-50%); }
.next-lb  { top:50%;  left:20px;  width:50px; height:50px; font-size:26px; transform:translateY(-50%); }
.counter-lb {
    position:absolute; bottom:20px; left:50%; transform:translateX(-50%);
    color:#fff; background:rgba(0,0,0,.55); padding:5px 16px;
    border-radius:20px; font-size:14px; pointer-events:none;
}

/* ════ Map ═════════════════════════════════════ */
#leafletMap {
    height:360px; width:100%;
    border-radius:0 0 14px 14px;
}

/* ═══ Map tabs ════════════════════════════════ */
.map-header {
    background:var(--bg-card);
    padding:14px 20px;
    border-bottom:1px solid var(--border);
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:8px;
}
.map-footer {
    background:var(--bg-card);
    padding:10px 16px;
    border-top:1px solid var(--border);
    border-radius:0 0 14px 14px;
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    align-items:center;
}

/* ═══ Leaflet dark-mode popup ═════════════════ */
body.dark-mode .leaflet-popup-content-wrapper {
    background:#0f1c2e;
    color:#e2e8f0;
    border:1px solid rgba(52,211,153,.2);
}
body.dark-mode .leaflet-popup-tip {
    background:#0f1c2e;
}
body.dark-mode .leaflet-control-zoom a {
    background:#0f1c2e;
    color:#34d399;
    border-color:rgba(52,211,153,.2);
}
body.dark-mode .leaflet-tile-pane { filter:brightness(.85) saturate(.9); }
</style>

<!-- Lightbox -->
<div id="lightbox">
    <button class="lb-btn close-lb" onclick="closeLightbox()">✕</button>
    <button class="lb-btn prev-lb"  onclick="changeLb(-1)">&#8250;</button>
    <img id="lbImg" src="" alt="">
    <button class="lb-btn next-lb"  onclick="changeLb(1)">&#8249;</button>
    <div class="counter-lb" id="lbCounter"></div>
</div>

<div class="container my-5">
<div class="row">

    <!-- ══════════════════════════════════════════
         القسم الأيسر
    ══════════════════════════════════════════ -->
    <div class="col-md-8">

        <!-- معرض الصور -->
        <?php if (!empty($images)): ?>
            <div style="position:relative;">
                <img id="mainImg"
                     src="/football-booking/assets/images/fields/<?php echo htmlspecialchars($images[0]['image_path']); ?>"
                     class="gallery-main-img mb-3"
                     alt="<?php echo htmlspecialchars($field['field_name']); ?>"
                     onclick="openLightbox(0)">
                <?php if (count($images) > 1): ?>
                <div style="position:absolute;bottom:20px;left:16px;
                            background:rgba(0,0,0,.6);color:#fff;
                            padding:5px 12px;border-radius:20px;font-size:13px;">
                    <i class="fas fa-images"></i> <?php echo count($images); ?> صور
                </div>
                <?php endif; ?>
            </div>

            <?php if (count($images) > 1): ?>
            <div style="display:grid;
                        grid-template-columns:repeat(<?php echo min(count($images),5); ?>,1fr);
                        gap:10px;margin-bottom:20px;">
                <?php foreach ($images as $idx => $img): ?>
                <img src="/football-booking/assets/images/fields/<?php echo htmlspecialchars($img['image_path']); ?>"
                     class="gallery-thumb <?php echo $idx===0?'active':''; ?>"
                     alt="صورة <?php echo $idx+1; ?>"
                     onclick="switchMain(this,'<?php echo htmlspecialchars($img['image_path']); ?>',<?php echo $idx; ?>)">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="gallery-placeholder mb-4">
                <i class="fas fa-futbol fa-8x" style="color:rgba(255,255,255,.3);"></i>
            </div>
        <?php endif; ?>

        <!-- تفاصيل الملعب -->
        <div class="field-details">
            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="gap:8px;">
                <h2 class="mb-0"><?php echo htmlspecialchars($field['field_name']); ?></h2>
                <span class="badge badge-success" style="font-size:.95rem;padding:8px 16px;">
                    ⚽ ملعب <?php echo htmlspecialchars($field['field_type']); ?>
                </span>
            </div>
            <ul class="field-features">
                <li>
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>الموقع:</strong>
                    <?php echo htmlspecialchars($field['city']); ?> —
                    <?php echo htmlspecialchars($field['address']); ?>
                </li>
                <li>
                    <i class="fas fa-money-bill-wave"></i>
                    <strong>السعر:</strong>
                    <span class="price-tag"><?php echo number_format($field['price_per_hour'],0); ?> ج.س / ساعة</span>
                </li>
                <li>
                    <i class="fas fa-clock"></i>
                    <strong>أوقات العمل:</strong>
                    من <?php echo date('h:i A', strtotime($field['opening_time'])); ?>
                    إلى <?php echo date('h:i A', strtotime($field['closing_time'])); ?>
                </li>
                <?php if ($field['has_lighting']):   ?><li><i class="fas fa-lightbulb"></i>  <strong>إضاءة ليلية متوفرة</strong></li><?php endif; ?>
                <?php if ($field['has_parking']):    ?><li><i class="fas fa-parking"></i>    <strong>موقف سيارات متوفر</strong></li><?php endif; ?>
                <?php if ($field['has_changing_rooms']): ?><li><i class="fas fa-door-open"></i> <strong>غرف تغيير ملابس</strong></li><?php endif; ?>
                <li><i class="fas fa-user"></i>  <strong>المالك:</strong> <?php echo htmlspecialchars($field['owner_name']); ?></li>
                <li><i class="fas fa-phone"></i> <strong>للتواصل:</strong> <?php echo htmlspecialchars($field['owner_phone']); ?></li>
            </ul>
            <?php if (!empty($field['description'])): ?>
            <div class="mt-4">
                <h5>📝 وصف الملعب</h5>
                <p style="color:var(--text-secondary);line-height:1.8;">
                    <?php echo nl2br(htmlspecialchars($field['description'])); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- ══════════════════════════════════════
             🗺️  الخريطة — Leaflet (لا تُحجب أبداً)
        ══════════════════════════════════════ -->
        <div class="card mt-4" style="overflow:hidden;border-radius:14px;">

            <!-- رأس الخريطة -->
            <div class="map-header">
                <h5 class="mb-0" style="color:var(--text-heading);">
                    <i class="fas fa-map-marked-alt"></i> موقع الملعب
                    <?php if ($map_precise): ?>
                        <span class="badge badge-success" style="font-size:11px;margin-right:6px;">
                            ✅ موقع دقيق
                        </span>
                    <?php else: ?>
                        <span class="badge badge-warning" style="font-size:11px;margin-right:6px;">
                            ⚠️ موقع تقريبي
                        </span>
                    <?php endif; ?>
                </h5>
                <!-- أزرار التبويب -->
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-success btn-sm map-tab active"
                            data-tab="map" onclick="switchTab(this,'map')">
                        🗺️ خريطة
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm map-tab"
                            data-tab="satellite" onclick="switchTab(this,'satellite')">
                        🛰️ قمر صناعي
                    </button>
                </div>
            </div>

            <!-- خريطة Leaflet -->
            <div id="leafletMap"></div>

            <!-- أزرار أسفل الخريطة -->
            <div class="map-footer">
                <a href="<?php echo htmlspecialchars($open_link); ?>"
                   target="_blank" class="btn btn-success btn-sm">
                    <i class="fab fa-google"></i> فتح في Google Maps
                </a>
                <a href="https://www.openstreetmap.org/?mlat=<?php echo $map_lat; ?>&mlon=<?php echo $map_lng; ?>&zoom=17"
                   target="_blank" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-map"></i> OpenStreetMap
                </a>
                <?php if (!$map_precise): ?>
                <span style="color:var(--text-muted);font-size:12px;align-self:center;">
                    أضف رابط Google Maps للموقع الدقيق
                </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- المراجعات -->
        <?php if ($reviews->num_rows > 0): ?>
        <div class="card mt-4">
            <div class="card-body">
                <h5><i class="fas fa-star" style="color:#f59e0b;"></i> تقييمات العملاء</h5>
                <hr>
                <?php while ($review = $reviews->fetch_assoc()): ?>
                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                        <div class="rating">
                            <?php for ($i=1;$i<=5;$i++): ?>
                                <i class="fas fa-star" style="color:<?php echo $i<=$review['rating']?'#f59e0b':'#e5e7eb';?>;"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p class="mb-1"><?php echo htmlspecialchars($review['comment']); ?></p>
                    <small class="text-muted"><?php echo format_arabic_date($review['created_at']); ?></small>
                    <hr>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ══════════════════════════════════════════
         نموذج الحجز (يمين)
    ══════════════════════════════════════════ -->
    <div class="col-md-4">
        <div class="card sticky-top" style="top:20px;">
            <div class="card-body">
                <h5 class="card-title text-center mb-4">
                    <i class="fas fa-calendar-check"></i> احجز الآن
                </h5>

                <?php if (is_logged_in() && is_customer()): ?>
                    <?php if (isset($_SESSION['error_message'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        </div>
                    <?php endif; ?>

                    <form action="/football-booking/customer/book_field.php" method="POST" id="bookingForm">
                        <input type="hidden" name="field_id"       value="<?php echo $field['id']; ?>">
                        <input type="hidden" name="price_per_hour" id="price_per_hour"
                               value="<?php echo $field['price_per_hour']; ?>">

                        <div class="form-group">
                            <label>📅 تاريخ الحجز</label>
                            <input type="date" class="form-control" name="booking_date"
                                   id="booking_date" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>🕐 وقت البدء</label>
                            <input type="time" class="form-control" name="start_time" id="start_time"
                                   min="<?php echo $field['opening_time']; ?>"
                                   max="<?php echo $field['closing_time']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>🕐 وقت الانتهاء</label>
                            <input type="time" class="form-control" name="end_time" id="end_time"
                                   min="<?php echo $field['opening_time']; ?>"
                                   max="<?php echo $field['closing_time']; ?>" required>
                        </div>

                        <input type="hidden" name="total_hours" id="total_hours">
                        <input type="hidden" name="total_price" id="total_price">

                        <div class="alert alert-info" id="priceDisplay">
                            <i class="fas fa-info-circle"></i> اختر الأوقات لحساب السعر
                        </div>

                        <div class="form-group">
                            <label>💳 طريقة الدفع</label>
                            <select class="form-control" name="payment_method" id="payment_method" required>
                                <option value="cash">💵 نقداً</option>
                                <option value="card">💳 بطاقة ائتمانية</option>
                            </select>
                        </div>

                        <div id="cardFields" style="display:none;">
                            <div class="alert alert-warning">
                                <i class="fas fa-credit-card"></i> بيانات البطاقة
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control" name="card_number"
                                       id="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                            <div class="form-group">
                                <input type="text" class="form-control" name="card_holder"
                                       id="card_holder" placeholder="اسم حامل البطاقة">
                            </div>
                            <div class="row">
                                <div class="col-6">
                                    <input type="text" class="form-control card-expiry"
                                           placeholder="MM/YY" maxlength="5">
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control" placeholder="CVV" maxlength="3">
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-3">
                            <label>📝 ملاحظات</label>
                            <textarea class="form-control" name="customer_notes" rows="2"
                                      placeholder="أي ملاحظات..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-success btn-block btn-lg">
                            <i class="fas fa-check"></i> تأكيد الحجز
                        </button>
                    </form>

                <?php elseif (is_logged_in()): ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle"></i>
                        <p class="mb-0">الحجز متاح للعملاء فقط</p>
                    </div>
                <?php else: ?>
                    <div class="text-center">
                        <p class="text-muted mb-3">يجب تسجيل الدخول أولاً للحجز</p>
                        <a href="/football-booking/auth/login.php" class="btn btn-primary btn-block">
                            <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                        </a>
                        <a href="/football-booking/auth/register.php" class="btn btn-success btn-block mt-2">
                            <i class="fas fa-user-plus"></i> تسجيل حساب جديد
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- /row -->
</div><!-- /container -->

<!-- ══════════════════════════════════════════════
     JavaScript
══════════════════════════════════════════════ -->
<script>
// ─────────────────────────────────────────
// 🗺️  Leaflet Map
// ─────────────────────────────────────────
(function () {
    var lat      = <?php echo json_encode($map_lat); ?>;
    var lng      = <?php echo json_encode($map_lng); ?>;
    var precise  = <?php echo $map_precise ? 'true' : 'false'; ?>;
    var name     = <?php echo json_encode(htmlspecialchars($field['field_name'])); ?>;
    var city     = <?php echo json_encode(htmlspecialchars($field['city'])); ?>;
    var address  = <?php echo json_encode(htmlspecialchars($field['address'])); ?>;
    var price    = <?php echo json_encode(number_format($field['price_per_hour'], 0)); ?>;
    var openLink = <?php echo json_encode($open_link); ?>;

    // طبقتا الخريطة
    var streetLayer = L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        { attribution: '© <a href="https://www.openstreetmap.org">OpenStreetMap</a>', maxZoom: 19 }
    );
    var satelliteLayer = L.tileLayer(
        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
        { attribution: '© Esri', maxZoom: 19 }
    );

    // تهيئة الخريطة
    var map = L.map('leafletMap', {
        center: [lat, lng],
        zoom: precise ? 17 : 14,
        layers: [streetLayer],
        zoomControl: true,
        scrollWheelZoom: false    // منع التمرير العرضي
    });

    // تفعيل التمرير عند النقر على الخريطة
    map.on('click', function () { map.scrollWheelZoom.enable(); });
    map.on('mouseout', function () { map.scrollWheelZoom.disable(); });

    // أيقونة مخصصة
    var fieldIcon = L.divIcon({
        html: '<div style="background:linear-gradient(135deg,#16a34a,#15803d);'
              + 'color:#fff;border-radius:50%;width:44px;height:44px;'
              + 'display:flex;align-items:center;justify-content:center;'
              + 'font-size:22px;box-shadow:0 4px 14px rgba(0,0,0,.35);'
              + 'border:3px solid rgba(255,255,255,.6);">⚽</div>',
        iconSize:   [44, 44],
        iconAnchor: [22, 44],
        popupAnchor:[0, -46],
        className:  ''
    });

    // علامة على الخريطة
    var marker = L.marker([lat, lng], { icon: fieldIcon })
        .addTo(map)
        .bindPopup(
            '<div style="text-align:right;direction:rtl;min-width:200px;font-family:Tajawal,Tahoma,sans-serif;">'
            + '<strong style="font-size:15px;color:#16a34a;">⚽ ' + name + '</strong><br>'
            + '<span style="font-size:13px;">📍 ' + city + ' — ' + address + '</span><br>'
            + '<span style="font-size:13px;">💰 ' + price + ' ج.س / ساعة</span><br><br>'
            + '<a href="' + openLink + '" target="_blank" '
            + 'style="background:#16a34a;color:#fff;padding:5px 12px;border-radius:6px;'
            + 'text-decoration:none;font-size:12px;">فتح في Google Maps ↗</a>'
            + '</div>',
            { maxWidth: 240 }
        )
        .openPopup();

    // دائرة تحديد المنطقة
    L.circle([lat, lng], {
        color:       '#16a34a',
        fillColor:   '#16a34a',
        fillOpacity: 0.08,
        radius:      precise ? 100 : 600,
        weight:      1.5
    }).addTo(map);

    // تبديل طبقة الخريطة (خريطة / قمر صناعي)
    window.switchTab = function (btn, tab) {
        if (tab === 'satellite') {
            map.removeLayer(streetLayer);
            map.addLayer(satelliteLayer);
        } else {
            map.removeLayer(satelliteLayer);
            map.addLayer(streetLayer);
        }
        document.querySelectorAll('.map-tab').forEach(function (b) {
            b.classList.remove('btn-success');
            b.classList.add('btn-outline-success');
        });
        btn.classList.remove('btn-outline-success');
        btn.classList.add('btn-success');
    };

    // تطبيق dark mode على الخريطة فور التحميل
    if (document.body.classList.contains('dark-mode')) {
        document.querySelector('.leaflet-tile-pane') &&
        (document.querySelector('.leaflet-tile-pane').style.filter = 'brightness(.85) saturate(.9)');
    }

    // تحديث الخريطة عند تغيير الحجم
    window.addEventListener('resize', function () { map.invalidateSize(); });
})();


// ─────────────────────────────────────────
// 🖼️  Gallery + Lightbox
// ─────────────────────────────────────────
var allImages = <?php echo json_encode(
    array_map(fn($img) => '/football-booking/assets/images/fields/' . $img['image_path'], $images)
); ?>;
var currentLbIndex = 0;

function switchMain(thumb, imgPath, idx) {
    document.getElementById('mainImg').src =
        '/football-booking/assets/images/fields/' + imgPath;
    document.querySelectorAll('.gallery-thumb').forEach(function (t) { t.classList.remove('active'); });
    thumb.classList.add('active');
    currentLbIndex = idx;
}
function openLightbox(idx) {
    currentLbIndex = idx;
    document.getElementById('lbImg').src        = allImages[idx];
    document.getElementById('lbCounter').textContent = (idx+1) + ' / ' + allImages.length;
    document.getElementById('lightbox').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('lightbox').classList.remove('show');
    document.body.style.overflow = '';
}
function changeLb(dir) {
    currentLbIndex = (currentLbIndex + dir + allImages.length) % allImages.length;
    document.getElementById('lbImg').src             = allImages[currentLbIndex];
    document.getElementById('lbCounter').textContent = (currentLbIndex+1) + ' / ' + allImages.length;
}
document.getElementById('lightbox').addEventListener('click', function (e) {
    if (e.target === this) closeLightbox();
});
document.addEventListener('keydown', function (e) {
    if (!document.getElementById('lightbox').classList.contains('show')) return;
    if (e.key === 'ArrowRight') changeLb(-1);
    if (e.key === 'ArrowLeft')  changeLb(1);
    if (e.key === 'Escape')     closeLightbox();
});
</script>

<?php include 'includes/footer.php'; ?>