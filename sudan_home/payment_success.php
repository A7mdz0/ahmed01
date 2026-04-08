<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$property_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$amount = isset($_GET['amount']) ? floatval($_GET['amount']) : 0;

// توليد رقم طلب عشوائي
$order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم الدفع بنجاح - دار السودان</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            direction: rtl;
            padding: 2rem;
        }
        .success-container {
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            text-align: center;
            animation: slideUp 0.5s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .success-icon {
            font-size: 5rem;
            margin-bottom: 1rem;
            animation: bounce 1s ease;
        }
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }
        h1 {
            color: #10b981;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }
        .order-details {
            background: #f9fafb;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            text-align: right;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            color: #666;
            font-weight: 600;
        }
        .detail-value {
            color: #333;
            font-weight: bold;
        }
        .amount {
            color: #10b981;
            font-size: 1.5rem;
        }
        .order-number {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.3rem;
            font-weight: bold;
        }
        .info-box {
            background: #e0f2fe;
            border: 2px solid #0ea5e9;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: right;
        }
        .info-box strong {
            display: block;
            color: #0c4a6e;
            margin-bottom: 0.5rem;
        }
        .info-box p {
            color: #075985;
            line-height: 1.6;
        }
        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .btn {
            padding: 1rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }
        .confetti {
            position: fixed;
            width: 10px;
            height: 10px;
            background: #667eea;
            animation: confetti-fall 3s linear;
            opacity: 0;
        }
        @keyframes confetti-fall {
            0% { transform: translateY(-100vh) rotate(0deg); opacity: 1; }
            100% { transform: translateY(100vh) rotate(720deg); opacity: 0; }
        }
        @media (max-width: 768px) {
            .actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h1>تم الدفع بنجاح!</h1>
        <p class="subtitle">تهانينا! تمت عملية الشراء بنجاح</p>

        <div class="order-details">
            <div class="detail-row">
                <span class="detail-label">رقم الطلب:</span>
                <span class="detail-value order-number"><?= $order_number ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">التاريخ:</span>
                <span class="detail-value"><?= date('Y-m-d H:i') ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">المبلغ المدفوع:</span>
                <span class="detail-value amount"><?= number_format($amount) ?> جنيه</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">طريقة الدفع:</span>
                <span class="detail-value">بطاقة ائتمان</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">الحالة:</span>
                <span class="detail-value" style="color: #10b981;">✓ مكتمل</span>
            </div>
        </div>

        <div class="info-box">
            <strong>📧 ما هي الخطوات التالية؟</strong>
            <p>
                ✓ تم إرسال إيصال الدفع إلى بريدك الإلكتروني<br>
                ✓ سيتواصل معك فريق المبيعات خلال 24 ساعة<br>
                ✓ سيتم جدولة موعد معاينة العقار<br>
                ✓ ستتلقى جميع المستندات القانونية
            </p>
        </div>

        <div class="actions">
            <a href="property_details.php?id=<?= $property_id ?>" class="btn btn-secondary">
                📄 عرض تفاصيل العقار
            </a>
            <a href="index.php" class="btn btn-primary">
                🏠 العودة للرئيسية
            </a>
        </div>

        <p style="margin-top: 2rem; color: #999; font-size: 0.9rem;">
            رقم الدعم: +249123456789 | البريد: support@sudanhome.sd
        </p>
    </div>

    <script>
        // إضافة تأثير الاحتفال
        function createConfetti() {
            for (let i = 0; i < 50; i++) {
                setTimeout(() => {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.background = ['#667eea', '#764ba2', '#10b981', '#f59e0b'][Math.floor(Math.random() * 4)];
                    confetti.style.animationDelay = Math.random() * 0.5 + 's';
                    document.body.appendChild(confetti);
                    
                    setTimeout(() => confetti.remove(), 3000);
                }, i * 30);
            }
        }
        
        createConfetti();
    </script>
</body>
</html>