<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$property_id) {
    header('Location: index.php');
    exit;
}

// جلب بيانات العقار
$stmt = $pdo->prepare("
    SELECT p.*, l.city, l.district, u.full_name as owner_name, u.phone as owner_phone
    FROM properties p
    LEFT JOIN locations l ON p.location_id = l.location_id
    LEFT JOIN users u ON p.owner_id = u.user_id
    WHERE p.property_id = ? AND p.status = 'active'
");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    header('Location: index.php');
    exit;
}

// جلب الصورة الرئيسية
$stmt = $pdo->prepare("SELECT image_path FROM property_images WHERE property_id = ? AND is_primary = 1 LIMIT 1");
$stmt->execute([$property_id]);
$main_image = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إتمام عملية الشراء - دار السودان</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
            direction: rtl;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .back-btn {
            background: transparent;
            color: #667eea;
            border: 2px solid #667eea;
            padding: 0.7rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-btn:hover {
            background: #667eea;
            color: white;
        }
        .checkout-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 2rem;
        }
        .section {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .section-title {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e0e0e0;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 600;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.9rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .payment-method {
            border: 2px solid #e0e0e0;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        .payment-method.active {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        .payment-method input[type="radio"] {
            display: none;
        }
        .payment-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        .property-summary {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .property-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        .property-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .property-location {
            color: #666;
            margin-bottom: 1rem;
        }
        .price-breakdown {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 10px;
        }
        .price-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .price-item:last-child {
            border-bottom: none;
            font-size: 1.3rem;
            font-weight: bold;
            color: #667eea;
            padding-top: 1rem;
            margin-top: 0.5rem;
            border-top: 2px solid #667eea;
        }
        .btn-pay {
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 1.5rem;
        }
        .btn-pay:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .btn-pay:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .security-badge {
            background: #d1fae5;
            border: 2px solid #10b981;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            color: #065f46;
            font-weight: 600;
            margin-top: 1rem;
        }
        .card-icons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1rem;
        }
        .card-icon {
            font-size: 2rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: none;
        }
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
            .form-row, .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🏡 دار السودان</div>
            <a href="property_details.php?id=<?= $property_id ?>" class="back-btn">← العودة للعقار</a>
        </div>

        <div id="alert" class="alert"></div>

        <div class="checkout-grid">
            <!-- قسم الدفع -->
            <div>
                <div class="section">
                    <h2 class="section-title">💳 معلومات الدفع</h2>
                    
                    <label style="display: block; margin-bottom: 1rem; color: #333; font-weight: 600;">اختر طريقة الدفع:</label>
                    <div class="payment-methods">
                        <label class="payment-method active" onclick="selectPayment(this, 'card')">
                            <input type="radio" name="payment_method" value="card" checked>
                            <div class="payment-icon">💳</div>
                            <div style="font-weight: 600;">بطاقة ائتمان</div>
                        </label>
                        
                        <label class="payment-method" onclick="selectPayment(this, 'bank')">
                            <input type="radio" name="payment_method" value="bank">
                            <div class="payment-icon">🏦</div>
                            <div style="font-weight: 600;">تحويل بنكي</div>
                        </label>
                        
                        <label class="payment-method" onclick="selectPayment(this, 'wallet')">
                            <input type="radio" name="payment_method" value="wallet">
                            <div class="payment-icon">📱</div>
                            <div style="font-weight: 600;">محفظة إلكترونية</div>
                        </label>
                        
                        <label class="payment-method" onclick="selectPayment(this, 'cash')">
                            <input type="radio" name="payment_method" value="cash">
                            <div class="payment-icon">💵</div>
                            <div style="font-weight: 600;">دفع نقدي</div>
                        </label>
                    </div>

                    <!-- نموذج بطاقة الائتمان -->
                    <div id="cardForm">
                        <div class="form-group">
                            <label>رقم البطاقة</label>
                            <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>تاريخ الانتهاء</label>
                                <input type="text" id="cardExpiry" placeholder="MM/YY" maxlength="5">
                            </div>
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="text" id="cardCVV" placeholder="123" maxlength="3">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>اسم حامل البطاقة</label>
                            <input type="text" id="cardName" placeholder="الاسم كما هو مكتوب على البطاقة">
                        </div>

                        <div class="card-icons">
                            <span class="card-icon" title="Visa">💳</span>
                            <span class="card-icon" title="Mastercard">💳</span>
                            <span class="card-icon" title="American Express">💳</span>
                        </div>
                    </div>
                </div>

                <div class="section" style="margin-top: 2rem;">
                    <h2 class="section-title">📋 معلومات المشتري</h2>
                    
                    <div class="form-group">
                        <label>الاسم الكامل</label>
                        <input type="text" id="buyerName" value="<?= htmlspecialchars($_SESSION['full_name']) ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>رقم الهاتف</label>
                            <input type="tel" id="buyerPhone" placeholder="+249123456789">
                        </div>
                        <div class="form-group">
                            <label>البريد الإلكتروني</label>
                            <input type="email" id="buyerEmail" value="<?= htmlspecialchars($_SESSION['email']) ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>العنوان</label>
                        <input type="text" id="buyerAddress" placeholder="عنوان الإقامة الحالي">
                    </div>

                    <div class="form-group">
                        <label>ملاحظات إضافية (اختياري)</label>
                        <textarea id="buyerNotes" style="min-height: 100px;" placeholder="أي ملاحظات أو طلبات خاصة..."></textarea>
                    </div>
                </div>
            </div>

            <!-- ملخص الطلب -->
            <div>
                <div class="section">
                    <h2 class="section-title">📦 ملخص الطلب</h2>
                    
                    <div class="property-summary">
                        <?php if ($main_image): ?>
                            <img src="<?= htmlspecialchars($main_image) ?>" alt="<?= htmlspecialchars($property['title']) ?>" class="property-image">
                        <?php else: ?>
                            <div style="width: 100%; height: 200px; background: linear-gradient(135deg, #e0e0e0, #f5f5f5); display: flex; align-items: center; justify-content: center; border-radius: 10px; margin-bottom: 1rem; color: #999; font-size: 3rem;">🏠</div>
                        <?php endif; ?>
                        
                        <div class="property-title"><?= htmlspecialchars($property['title']) ?></div>
                        <div class="property-location">📍 <?= htmlspecialchars($property['city']) ?> - <?= htmlspecialchars($property['district']) ?></div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                            <div>
                                <div style="color: #999; font-size: 0.85rem;">المساحة</div>
                                <div style="font-weight: bold;"><?= number_format($property['area']) ?> م²</div>
                            </div>
                            <div>
                                <div style="color: #999; font-size: 0.85rem;">النوع</div>
                                <div style="font-weight: bold;"><?= htmlspecialchars($property['property_type']) ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="price-breakdown">
                        <div class="price-item">
                            <span>سعر العقار</span>
                            <span><?= number_format($property['price']) ?> جنيه</span>
                        </div>
                        <div class="price-item">
                            <span>رسوم الخدمة (2%)</span>
                            <span><?= number_format($property['price'] * 0.02) ?> جنيه</span>
                        </div>
                        <div class="price-item">
                            <span>الضريبة (5%)</span>
                            <span><?= number_format($property['price'] * 0.05) ?> جنيه</span>
                        </div>
                        <div class="price-item">
                            <span>الإجمالي</span>
                            <span id="totalAmount"><?= number_format($property['price'] * 1.07) ?> جنيه</span>
                        </div>
                    </div>

                    <div class="security-badge">
                        🔒 عملية دفع آمنة ومشفرة بنسبة 100%
                    </div>

                    <button class="btn-pay" id="btnPay" onclick="processPayment()">
                        💳 تأكيد الدفع وإتمام الشراء
                    </button>

                    <div style="text-align: center; color: #999; font-size: 0.85rem; margin-top: 1rem;">
                        بالنقر على "تأكيد الدفع"، أنت توافق على <a href="#" style="color: #667eea;">الشروط والأحكام</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectPayment(element, type) {
            document.querySelectorAll('.payment-method').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            element.querySelector('input').checked = true;
            
            // إخفاء/إظهار نموذج البطاقة
            const cardForm = document.getElementById('cardForm');
            if (type === 'card') {
                cardForm.style.display = 'block';
            } else {
                cardForm.style.display = 'none';
            }
        }

        async function processPayment() {
            const btn = document.getElementById('btnPay');
            const alert = document.getElementById('alert');
            
            // جمع البيانات
            const formData = new FormData();
            formData.append('property_id', <?= $property_id ?>);
            formData.append('payment_method', document.querySelector('input[name="payment_method"]:checked').value);
            formData.append('buyer_name', document.getElementById('buyerName').value);
            formData.append('buyer_phone', document.getElementById('buyerPhone').value);
            formData.append('buyer_email', document.getElementById('buyerEmail').value);
            formData.append('buyer_address', document.getElementById('buyerAddress').value);
            formData.append('buyer_notes', document.getElementById('buyerNotes').value);

            // التحقق من البيانات
            if (!formData.get('buyer_name') || !formData.get('buyer_phone') || !formData.get('buyer_email')) {
                alert.className = 'alert alert-error';
                alert.textContent = '✗ يرجى ملء جميع البيانات المطلوبة';
                alert.style.display = 'block';
                window.scrollTo(0, 0);
                return;
            }

            // تعطيل الزر
            btn.disabled = true;
            btn.textContent = '⏳ جاري معالجة الدفع...';
            
            alert.className = 'alert alert-success';
            alert.textContent = 'جاري معالجة عملية الدفع...';
            alert.style.display = 'block';
            window.scrollTo(0, 0);

            try {
                const response = await fetch('api/process_payment.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert.className = 'alert alert-success';
                    alert.textContent = '✓ تمت عملية الدفع بنجاح! جاري التحويل...';
                    
                    setTimeout(() => {
                        window.location.href = `payment_success.php?id=<?= $property_id ?>&amount=${data.total_amount}`;
                    }, 1500);
                } else {
                    alert.className = 'alert alert-error';
                    alert.textContent = '✗ ' + data.message;
                    btn.disabled = false;
                    btn.textContent = '💳 تأكيد الدفع وإتمام الشراء';
                }
            } catch (error) {
                console.error('Payment Error:', error);
                alert.className = 'alert alert-error';
                alert.textContent = '✗ حدث خطأ أثناء معالجة الدفع. يرجى المحاولة مرة أخرى.';
                btn.disabled = false;
                btn.textContent = '💳 تأكيد الدفع وإتمام الشراء';
            }
        }
    </script>
</body>
</html>