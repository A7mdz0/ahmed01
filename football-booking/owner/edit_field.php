<?php
session_start();
require_once '../auth/check_auth.php';
require_user_type('owner');
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['id'])) redirect('my_fields.php');

$field_id = intval($_GET['id']);
$owner_id = $_SESSION['user_id'];

// جلب بيانات الملعب
$sql = "SELECT * FROM fields WHERE id = ? AND owner_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $field_id, $owner_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) redirect('my_fields.php');
$field = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $field_name = clean_input($_POST['field_name']);
    $city = clean_input($_POST['city']);
    $address = clean_input($_POST['address']);
    $description = clean_input($_POST['description']);
    $price_per_hour = floatval($_POST['price_per_hour']);
    $field_type = clean_input($_POST['field_type']);
    $has_lighting = isset($_POST['has_lighting']) ? 1 : 0;
    $has_parking = isset($_POST['has_parking']) ? 1 : 0;
    $has_changing_rooms = isset($_POST['has_changing_rooms']) ? 1 : 0;
    $opening_time = clean_input($_POST['opening_time']);
    $closing_time = clean_input($_POST['closing_time']);
    
    $sql = "UPDATE fields SET field_name=?, city=?, address=?, description=?, price_per_hour=?, 
            field_type=?, has_lighting=?, has_parking=?, has_changing_rooms=?, opening_time=?, closing_time=? 
            WHERE id=? AND owner_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssdsiisssii", $field_name, $city, $address, $description, $price_per_hour, 
                     $field_type, $has_lighting, $has_parking, $has_changing_rooms, $opening_time, $closing_time,
                     $field_id, $owner_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'تم تحديث الملعب بنجاح!';
        redirect('my_fields.php');
    }
}

$page_title = 'تعديل الملعب';
include '../includes/header.php';
?>
<div class="container my-5">
    <div class="page-header">
        <h2><i class="fas fa-edit"></i> تعديل الملعب</h2>
    </div>
    
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>اسم الملعب</label>
                            <input type="text" class="form-control" name="field_name" value="<?php echo htmlspecialchars($field['field_name']); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>المدينة</label>
                            <select class="form-control" name="city" required>
                                <option value="الخرطوم" <?php echo $field['city'] === 'الخرطوم' ? 'selected' : ''; ?>>الخرطوم</option>
                                <option value="الخرطوم بحري" <?php echo $field['city'] === 'الخرطوم بحري' ? 'selected' : ''; ?>>الخرطوم بحري</option>
                                <option value="أم درمان" <?php echo $field['city'] === 'أم درمان' ? 'selected' : ''; ?>>أم درمان</option>
                                <option value="بورتسودان" <?php echo $field['city'] === 'بورتسودان' ? 'selected' : ''; ?>>بورتسودان</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>العنوان</label>
                    <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($field['address']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>الوصف</label>
                    <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($field['description']); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>السعر بالساعة</label>
                            <input type="number" class="form-control" name="price_per_hour" step="0.01" value="<?php echo $field['price_per_hour']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>نوع الملعب</label>
                            <select class="form-control" name="field_type" required>
                                <option value="خماسي" <?php echo $field['field_type'] === 'خماسي' ? 'selected' : ''; ?>>خماسي</option>
                                <option value="سباعي" <?php echo $field['field_type'] === 'سباعي' ? 'selected' : ''; ?>>سباعي</option>
                                <option value="تساعي" <?php echo $field['field_type'] === 'تساعي' ? 'selected' : ''; ?>>تساعي</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>وقت الافتتاح</label>
                            <input type="time" class="form-control" name="opening_time" value="<?php echo $field['opening_time']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>وقت الإغلاق</label>
                            <input type="time" class="form-control" name="closing_time" value="<?php echo $field['closing_time']; ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>المميزات:</label><br>
                    <div class="custom-control custom-checkbox custom-control-inline">
                        <input type="checkbox" class="custom-control-input" id="has_lighting" name="has_lighting" <?php echo $field['has_lighting'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="has_lighting">إضاءة ليلية</label>
                    </div>
                    <div class="custom-control custom-checkbox custom-control-inline">
                        <input type="checkbox" class="custom-control-input" id="has_parking" name="has_parking" <?php echo $field['has_parking'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="has_parking">موقف سيارات</label>
                    </div>
                    <div class="custom-control custom-checkbox custom-control-inline">
                        <input type="checkbox" class="custom-control-input" id="has_changing_rooms" name="has_changing_rooms" <?php echo $field['has_changing_rooms'] ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="has_changing_rooms">غرف تغيير ملابس</label>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-check"></i> حفظ التعديلات</button>
                    <a href="my_fields.php" class="btn btn-secondary btn-lg"><i class="fas fa-times"></i> إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
