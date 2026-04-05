# 🏟️ نظام حجز ملاعب كرة القدم - السودان

![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![License](https://img.shields.io/badge/License-MIT-green)

## 📖 نظرة عامة

نظام ويب متكامل لحجز ملاعب كرة القدم في السودان، مصمم خصيصاً للعمل على بيئة XAMPP المحلية. يتيح النظام للعملاء حجز الملاعب بسهولة، ولأصحاب الملاعب إدارة ملاعبهم وحجوزاتهم بكفاءة.

## ✨ المميزات الرئيسية

### 🔐 نظام المصادقة والأمان
- تسجيل ودخول آمن مع تشفير كلمات المرور (password_hash)
- ثلاثة أنواع من المستخدمين (عميل، مالك ملعب، مدير)
- حماية من SQL Injection باستخدام Prepared Statements
- نظام Sessions محمي

### 👥 إدارة المستخدمين
- **العميل**: حجز الملاعب، متابعة الحجوزات، إلغاء الحجز
- **مالك الملعب**: إضافة/تعديل/حذف الملاعب، قبول/رفض الحجوزات
- **المدير**: التحكم الكامل بالنظام، إدارة المستخدمين والملاعب

### ⚽ إدارة الملاعب
- إضافة ملاعب بتفاصيل كاملة (الاسم، المدينة، السعر، المواصفات)
- رفع صور الملاعب
- تحديد أوقات العمل
- إضافة مميزات (إضاءة، موقف سيارات، غرف تغيير)

### 📅 نظام الحجوزات الذكي
- حجز ملعب بتاريخ ووقت محدد
- منع التعارض في المواعيد تلقائياً
- حساب السعر الإجمالي تلقائياً
- حالات متعددة للحجز (معلق، مؤكد، مرفوض، ملغي، مكتمل)

### 🔍 نظام البحث
- البحث حسب المدينة
- الفلترة حسب نوع الملعب (خماسي، سباعي، تساعي)
- الفلترة حسب السعر
- الفلترة حسب المميزات (إضاءة، موقف)

### 🎨 التصميم
- واجهة عربية بالكامل (RTL)
- تصميم متجاوب مع جميع الأجهزة (Mobile-First)
- استخدام Bootstrap 4
- أيقونات Font Awesome
- ألوان وتصميم احترافي

## 🛠️ المتطلبات التقنية

- **PHP**: 7.4 أو أحدث
- **MySQL**: 5.7 أو أحدث
- **Apache**: 2.4 أو أحدث
- **XAMPP**: أحدث إصدار (يشمل كل ما سبق)
- **المتصفح**: Chrome, Firefox, Safari, أو Edge (أحدث إصدار)

## 📦 التثبيت

### 1. تثبيت XAMPP

```bash
# قم بتحميل XAMPP من:
https://www.apachefriends.org

# ثبت XAMPP في المسار الافتراضي
```

### 2. نسخ المشروع

```bash
# انسخ مجلد football-booking إلى:
# Windows: C:\xampp\htdocs\
# Linux: /opt/lampp/htdocs/
# Mac: /Applications/XAMPP/htdocs/
```

### 3. إنشاء قاعدة البيانات

```sql
-- افتح phpMyAdmin: http://localhost/phpmyadmin
-- قم باستيراد الملف: database/football_booking.sql
```

### 4. إعداد الاتصال

```php
// تحقق من ملف: includes/db.php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // فارغة افتراضياً
define('DB_NAME', 'football_booking');
```

### 5. تشغيل المشروع

```
1. شغل Apache و MySQL من XAMPP Control Panel
2. افتح المتصفح واذهب إلى:
   http://localhost/football-booking
```

## 👤 الحسابات التجريبية

### حساب العميل
```
البريد: customer@example.sd
كلمة المرور: customer123
```

### حساب مالك الملعب
```
البريد: owner@example.sd
كلمة المرور: owner123
```

### حساب المدير
```
البريد: admin@footballbooking.sd
كلمة المرور: admin123
```

## 📁 هيكلية المشروع

```
football-booking/
├── index.php                 # الصفحة الرئيسية
├── search.php               # صفحة البحث
├── field_details.php        # تفاصيل الملعب
│
├── auth/                    # المصادقة
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── check_auth.php
│
├── customer/               # لوحة العميل
│   ├── dashboard.php
│   ├── book_field.php
│   ├── my_bookings.php
│   └── cancel_booking.php
│
├── owner/                  # لوحة المالك
│   ├── dashboard.php
│   ├── add_field.php
│   ├── edit_field.php
│   ├── delete_field.php
│   ├── my_fields.php
│   ├── bookings.php
│   └── manage_booking.php
│
├── admin/                  # لوحة الإدارة
│   ├── dashboard.php
│   ├── users.php
│   ├── all_fields.php
│   └── all_bookings.php
│
├── includes/               # ملفات مشتركة
│   ├── db.php
│   ├── functions.php
│   ├── header.php
│   └── footer.php
│
├── assets/                # الموارد
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── script.js
│   └── images/
│       └── fields/
│
└── database/              # قاعدة البيانات
    └── football_booking.sql
```

## 🗄️ قاعدة البيانات

### الجداول الرئيسية

#### users (المستخدمون)
- id, full_name, email, phone, password, user_type, created_at

#### fields (الملاعب)
- id, owner_id, field_name, city, address, price_per_hour, field_type, has_lighting, has_parking, has_changing_rooms, opening_time, closing_time

#### bookings (الحجوزات)
- id, customer_id, field_id, booking_date, start_time, end_time, total_hours, total_price, status

#### reviews (التقييمات)
- id, field_id, customer_id, booking_id, rating, comment

## 🔒 الأمان

- ✅ تشفير كلمات المرور باستخدام `password_hash`
- ✅ حماية من SQL Injection باستخدام Prepared Statements
- ✅ التحقق من المدخلات (Input Validation)
- ✅ منع الوصول غير المصرح به باستخدام Sessions
- ✅ تنظيف المدخلات من HTML/JavaScript
- ✅ CSRF Protection في النماذج الحساسة

## 📱 التوافق

- ✅ متوافق مع جميع المتصفحات الحديثة
- ✅ متجاوب مع الهواتف الذكية
- ✅ متجاوب مع الأجهزة اللوحية
- ✅ يدعم الشاشات الصغيرة والكبيرة

## 🚀 الميزات المستقبلية (اختياري)

- [ ] نظام الدفع الإلكتروني
- [ ] تطبيق موبايل (Android/iOS)
- [ ] نظام التقييمات والمراجعات
- [ ] إشعارات البريد الإلكتروني
- [ ] إشعارات SMS
- [ ] تقارير وإحصائيات متقدمة
- [ ] نظام الخصومات والعروض
- [ ] حجز متكرر (أسبوعي/شهري)

## 🐛 المشاكل الشائعة وحلولها

### المشكلة: خطأ في الاتصال بقاعدة البيانات
```
الحل: تحقق من بيانات الاتصال في includes/db.php
```

### المشكلة: لا تظهر الصور
```
الحل: تأكد من صلاحيات مجلد assets/images/fields/
chmod 777 assets/images/fields/ (Linux/Mac)
```

### المشكلة: Apache لا يعمل (Port 80 مشغول)
```
الحل: غير البورت في httpd.conf إلى 8080
```

## 📝 الترخيص

هذا المشروع مفتوح المصدر ومتاح للاستخدام التعليمي والتجاري.

## 👨‍💻 المطور

تم تطويره باستخدام:
- PHP (Procedural & OOP)
- MySQL
- HTML5, CSS3, JavaScript
- Bootstrap 4
- Font Awesome
- jQuery

## 🎓 الاستخدام التعليمي

هذا المشروع مناسب لـ:
- مشروع تخرج
- مشروع دراسي في مادة هندسة البرمجيات
- مشروع دراسي في مادة قواعد البيانات
- مشروع دراسي في مادة تطوير تطبيقات الويب
- نظام حقيقي بعد التخصيص

## 📞 الدعم

للدعم والاستفسارات:
- راجع ملف INSTALLATION_GUIDE.txt
- راجع التعليقات في الكود
- راجع قسم حل المشاكل الشائعة

## 🙏 شكر خاص

شكراً لاستخدامك هذا النظام. نتمنى أن يكون مفيداً لك!

---

**تم التطوير بـ ❤️ للسودان**
