-- ==========================================
-- قاعدة بيانات نظام حجز ملاعب كرة القدم
-- ==========================================

-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS football_booking 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE football_booking;

-- جدول المستخدمين
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('customer', 'owner', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_email (email),
    INDEX idx_user_type (user_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الملاعب
CREATE TABLE fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    field_name VARCHAR(150) NOT NULL,
    city VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    description TEXT,
    price_per_hour DECIMAL(10,2) NOT NULL,
    field_type ENUM('خماسي', 'سباعي', 'تساعي') DEFAULT 'خماسي',
    has_lighting BOOLEAN DEFAULT FALSE,
    has_parking BOOLEAN DEFAULT FALSE,
    has_changing_rooms BOOLEAN DEFAULT FALSE,
    image_path VARCHAR(255),
    opening_time TIME NOT NULL DEFAULT '08:00:00',
    closing_time TIME NOT NULL DEFAULT '23:00:00',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_city (city),
    INDEX idx_owner (owner_id),
    INDEX idx_price (price_per_hour)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول الحجوزات
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    field_id INT NOT NULL,
    booking_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    total_hours INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('معلق', 'مؤكد', 'مرفوض', 'ملغي', 'مكتمل') DEFAULT 'معلق',
    payment_method ENUM('cash', 'card') DEFAULT 'cash',
    payment_status ENUM('معلق', 'مدفوع', 'مرفوض') DEFAULT 'معلق',
    card_number VARCHAR(20),
    card_holder VARCHAR(100),
    customer_notes TEXT,
    owner_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE CASCADE,
    INDEX idx_customer (customer_id),
    INDEX idx_field (field_id),
    INDEX idx_date (booking_date),
    INDEX idx_status (status),
    UNIQUE KEY unique_booking (field_id, booking_date, start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول المراجعات (اختياري)
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_id INT NOT NULL,
    customer_id INT NOT NULL,
    booking_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (field_id) REFERENCES fields(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    INDEX idx_field (field_id),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج مستخدم مدير افتراضي
-- البريد: admin@footballbooking.sd
-- كلمة المرور: admin123
INSERT INTO users (full_name, email, phone, password, user_type) 
VALUES ('المدير العام', 'admin@footballbooking.sd', '0123456789', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- إدراج بيانات تجريبية لمالك ملعب
-- البريد: owner@example.sd
-- كلمة المرور: owner123
INSERT INTO users (full_name, email, phone, password, user_type) 
VALUES ('أحمد محمد عبدالله', 'owner@example.sd', '0912345678', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner');

-- إدراج بيانات تجريبية لعميل
-- البريد: customer@example.sd
-- كلمة المرور: customer123
INSERT INTO users (full_name, email, phone, password, user_type) 
VALUES ('خالد عبدالله إبراهيم', 'customer@example.sd', '0911111111', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer');

-- إدراج ملاعب تجريبية
INSERT INTO fields (owner_id, field_name, city, address, description, price_per_hour, field_type, has_lighting, has_parking, has_changing_rooms, opening_time, closing_time) 
VALUES 
(2, 'ملعب الأبطال', 'الخرطوم', 'الرياض - شارع الستين', 'ملعب خماسي حديث مع إضاءة ممتازة وأرضية عشب صناعي عالي الجودة. مناسب للمباريات الاحترافية والتدريبات.', 150.00, 'خماسي', TRUE, TRUE, TRUE, '08:00:00', '23:00:00'),
(2, 'ملعب النجوم', 'الخرطوم بحري', 'الكدرو - بجانب السوق الشعبي', 'ملعب سباعي واسع مع مواقف سيارات كبيرة ومرافق حديثة. يتسع لعدد كبير من المتفرجين.', 200.00, 'سباعي', TRUE, TRUE, TRUE, '09:00:00', '22:00:00'),
(2, 'ملعب الحي', 'أم درمان', 'الموردة - حي السلام', 'ملعب خماسي اقتصادي للأحياء السكنية. مثالي للأسر والشباب.', 100.00, 'خماسي', FALSE, FALSE, FALSE, '15:00:00', '21:00:00'),
(2, 'ملعب الشامل', 'الخرطوم', 'العمارات - شارع الجامعة', 'ملعب تساعي مع إضاءة ليلية وكافتيريا. مناسب للبطولات والفعاليات الرياضية.', 250.00, 'تساعي', TRUE, TRUE, TRUE, '07:00:00', '23:00:00');

-- إدراج حجوزات تجريبية
INSERT INTO bookings (customer_id, field_id, booking_date, start_time, end_time, total_hours, total_price, status, customer_notes)
VALUES
(3, 1, CURDATE() + INTERVAL 2 DAY, '16:00:00', '18:00:00', 2, 300.00, 'معلق', 'نريد حجز الملعب لمباراة ودية'),
(3, 2, CURDATE() + INTERVAL 3 DAY, '19:00:00', '21:00:00', 2, 400.00, 'مؤكد', 'مباراة بين الأصدقاء');
