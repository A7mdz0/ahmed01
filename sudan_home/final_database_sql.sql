-- ====================================
-- قاعدة بيانات دار السودان العقارية
-- Sudan Home Platform Database
-- ====================================

-- حذف قاعدة البيانات إذا كانت موجودة وإنشائها من جديد
DROP DATABASE IF EXISTS sudan_home_platform;
CREATE DATABASE sudan_home_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sudan_home_platform;

-- ====================================
-- جدول المستخدمين
-- ====================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'owner', 'customer') NOT NULL DEFAULT 'customer',
    profile_image VARCHAR(255) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    national_id VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_email (email),
    INDEX idx_user_type (user_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- جدول المدن والمناطق
-- ====================================
CREATE TABLE locations (
    location_id INT PRIMARY KEY AUTO_INCREMENT,
    city VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    parent_location_id INT DEFAULT NULL,
    FOREIGN KEY (parent_location_id) REFERENCES locations(location_id) ON DELETE SET NULL,
    INDEX idx_city (city)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- جدول العقارات
-- ====================================
CREATE TABLE properties (
    property_id INT PRIMARY KEY AUTO_INCREMENT,
    owner_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    property_type ENUM('شقة', 'فيلا', 'منزل', 'أرض', 'محل تجاري', 'مكتب') NOT NULL,
    listing_type ENUM('للبيع', 'للإيجار') NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    area DECIMAL(10, 2) NOT NULL,
    bedrooms INT DEFAULT NULL,
    bathrooms INT DEFAULT NULL,
    location_id INT NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10, 8) DEFAULT NULL,
    longitude DECIMAL(11, 8) DEFAULT NULL,
    is_verified TINYINT(1) DEFAULT 0,
    verification_date TIMESTAMP NULL,
    status ENUM('pending', 'active', 'sold', 'rented', 'rejected') DEFAULT 'pending',
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(location_id) ON DELETE RESTRICT,
    INDEX idx_property_type (property_type),
    INDEX idx_listing_type (listing_type),
    INDEX idx_price (price),
    INDEX idx_status (status),
    INDEX idx_location (location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- جدول صور العقارات
-- ====================================
CREATE TABLE property_images (
    image_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    INDEX idx_property (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- جدول المفضلات
-- ====================================
CREATE TABLE favorites (
    favorite_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    property_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- جدول الرسائل
-- ====================================
CREATE TABLE messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    property_id INT DEFAULT NULL,
    message_text TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE SET NULL,
    INDEX idx_receiver (receiver_id),
    INDEX idx_property (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- جدول التقييمات
-- ====================================
CREATE TABLE reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(property_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (property_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- جدول سجل النشاطات
-- ====================================
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================
-- إدراج البيانات التجريبية
-- ====================================

-- إدراج المدن والمناطق السودانية
INSERT INTO locations (city, district) VALUES
('الخرطوم', 'الخرطوم 1'),
('الخرطوم', 'الخرطوم 2'),
('الخرطوم', 'الرياض'),
('الخرطوم', 'العمارات'),
('أم درمان', 'الثورة'),
('أم درمان', 'أبو سعد'),
('بحري', 'الكدرو'),
('بحري', 'الخرطوم بحري'),
('بورتسودان', 'وسط المدينة'),
('بورتسودان', 'الميناء'),
('كسلا', 'وسط المدينة'),
('كسلا', 'الختمية');

-- إنشاء حساب المدير الرئيسي
-- البريد: admin@sudanhome.sd
-- كلمة المرور: Admin@123
INSERT INTO users (full_name, email, phone, password_hash, user_type, is_verified, is_active) 
VALUES (
    'المدير العام', 
    'admin@sudanhome.sd', 
    '+249123456789', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'admin', 
    1,
    1
);

-- إنشاء مستخدمين تجريبيين
INSERT INTO users (full_name, email, phone, password_hash, user_type, is_verified) VALUES
('أحمد محمد', 'ahmed@test.com', '+249111111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', 1),
('فاطمة علي', 'fatima@test.com', '+249222222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', 0),
('محمد حسن', 'mohamed@test.com', '+249333333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 1);

-- عقارات تجريبية
INSERT INTO properties (owner_id, title, description, property_type, listing_type, price, area, bedrooms, bathrooms, location_id, address, status, is_verified) VALUES
(2, 'فيلا فاخرة في الرياض', 'فيلا حديثة بتصميم عصري في منطقة الرياض الراقية. تحتوي على 5 غرف نوم رئيسية، صالة واسعة، مطبخ مجهز، حديقة خلفية.', 'فيلا', 'للبيع', 45000000.00, 350.00, 5, 4, 3, 'شارع الستين، الرياض، الخرطوم', 'active', 1),
(2, 'شقة للإيجار في الخرطوم 2', 'شقة حديثة 3 غرف في موقع متميز قريب من الخدمات والمواصلات.', 'شقة', 'للإيجار', 15000.00, 180.00, 3, 2, 2, 'الخرطوم 2، بالقرب من السوق', 'active', 1),
(3, 'أرض سكنية في بحري', 'أرض سكنية مميزة في موقع حيوي، مناسبة للبناء السكني أو التجاري.', 'أرض', 'للبيع', 28000000.00, 600.00, NULL, NULL, 7, 'الكدرو، بحري', 'pending', 0);

-- ====================================
-- رسائل النجاح
-- ====================================
SELECT 'تم إنشاء قاعدة البيانات بنجاح!' AS status;
SELECT COUNT(*) AS 'عدد المدن المضافة' FROM locations;
SELECT COUNT(*) AS 'عدد المستخدمين' FROM users;
SELECT COUNT(*) AS 'عدد العقارات' FROM properties;