-- =====================================================
-- ملف تحديث قاعدة البيانات - إضافة نظام الدفع
-- =====================================================
-- استخدم هذا الملف إذا كانت قاعدة البيانات موجودة مسبقاً

USE football_booking;

-- إضافة حقول الدفع لجدول الحجوزات
ALTER TABLE bookings 
ADD COLUMN payment_method ENUM('cash', 'card') DEFAULT 'cash' AFTER status,
ADD COLUMN payment_status ENUM('معلق', 'مدفوع', 'مرفوض') DEFAULT 'معلق' AFTER payment_method,
ADD COLUMN card_number VARCHAR(20) AFTER payment_status,
ADD COLUMN card_holder VARCHAR(100) AFTER card_number;

-- تحديث الحجوزات الموجودة (اختياري)
UPDATE bookings SET payment_method = 'cash', payment_status = 'معلق' WHERE payment_method IS NULL;

-- رسالة نجاح
SELECT 'تم تحديث قاعدة البيانات بنجاح! تمت إضافة نظام الدفع.' as message;
