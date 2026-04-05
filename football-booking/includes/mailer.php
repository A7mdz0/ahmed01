<?php
/**
 * includes/mailer.php
 * إرسال الإيميلات باستخدام PHPMailer + Gmail SMTP
 *
 * طريقة التثبيت:
 * 1. افتح Command Prompt في مجلد المشروع
 * 2. نفّذ: composer require phpmailer/phpmailer
 * 3. أو حمّل يدوياً من: https://github.com/PHPMailer/PHPMailer
 *    وضع مجلد src داخل: includes/PHPMailer/src/
 *
 * إعداد Gmail:
 * 1. فعّل 2-Step Verification في حسابك
 * 2. اذهب لـ: Google Account > Security > App passwords
 * 3. أنشئ App Password واستخدمه في MAIL_PASSWORD
 */

// ── اختر طريقة التحميل ──────────────────────────────────
// إذا استخدمت Composer:
// require_once __DIR__ . '/../vendor/autoload.php';

// إذا حمّلت يدوياً:
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── ⚙️ إعدادات SMTP — غيّر هذه القيم فقط ───────────────
define('MAIL_HOST',      'smtp.gmail.com');
define('MAIL_PORT',      587);
define('MAIL_USERNAME',  'ahmedatifahmedatif@gmail.com');   // ← بريدك
define('MAIL_PASSWORD',  'p b r ol v f o b b j q e v m s​
');    // ← App Password
define('MAIL_FROM',      'ahmedatifahmedatif@gmail.com');   // ← نفس البريد
define('MAIL_FROM_NAME', 'ملاعب السودان');
// ────────────────────────────────────────────────────────

/**
 * إرسال إيميل تأكيد الحجز للعميل
 *
 * @param string $to_email  بريد العميل
 * @param string $to_name   اسم العميل
 * @param array  $data      بيانات الحجز
 * @return bool
 */
function send_booking_confirmation($to_email, $to_name, $data) {
    $mail = new PHPMailer(true);
    try {
        // إعدادات SMTP
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        // المرسِل والمستقبِل
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        // محتوى الإيميل
        $mail->isHTML(true);
        $mail->Subject = '✅ تأكيد حجزك في ' . $data['field_name'];
        $mail->Body    = get_booking_email_html($data);
        $mail->AltBody = get_booking_email_text($data); // نسخة نص عادي

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * إرسال إيميل رفض الحجز للعميل
 */
function send_booking_rejection($to_email, $to_name, $data) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to_email, $to_name);

        $mail->isHTML(true);
        $mail->Subject = '❌ بخصوص طلب حجزك في ' . $data['field_name'];
        $mail->Body    = get_rejection_email_html($data);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer Error: ' . $mail->ErrorInfo);
        return false;
    }
}

// ═══════════════════════════════════════════════════════
//  قوالب الإيميل HTML
// ═══════════════════════════════════════════════════════

function get_booking_email_html($d) {
    $payment_label = ($d['payment_method'] === 'card') ? '💳 بطاقة ائتمانية' : '💵 نقداً (كاش)';
    $hours_label   = $d['total_hours'] . ' ' . ($d['total_hours'] == 1 ? 'ساعة' : 'ساعات');

    return '
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;direction:rtl;">

  <!-- Wrapper -->
  <table width="100%" cellpadding="0" cellspacing="0"
         style="background:#f0f4f8;padding:30px 0;">
    <tr><td align="center">

      <!-- Card -->
      <table width="600" cellpadding="0" cellspacing="0"
             style="background:#ffffff;border-radius:16px;
                    box-shadow:0 4px 20px rgba(0,0,0,.1);overflow:hidden;">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#16a34a,#15803d);
                     padding:36px 40px;text-align:center;">
            <div style="font-size:48px;margin-bottom:10px;">⚽</div>
            <h1 style="color:#ffffff;margin:0;font-size:26px;font-weight:bold;">
              تم تأكيد حجزك!
            </h1>
            <p style="color:rgba(255,255,255,.85);margin:8px 0 0;font-size:15px;">
              ملاعب السودان — نظام الحجز الإلكتروني
            </p>
          </td>
        </tr>

        <!-- Greeting -->
        <tr>
          <td style="padding:32px 40px 0;">
            <p style="color:#374151;font-size:16px;margin:0;">
              مرحباً <strong style="color:#16a34a;">' . htmlspecialchars($d['customer_name']) . '</strong> 👋
            </p>
            <p style="color:#6b7280;font-size:15px;margin:10px 0 0;line-height:1.7;">
              يسعدنا إخبارك بأن طلب حجزك قد تمّت الموافقة عليه. فيما يلي تفاصيل حجزك كاملةً.
            </p>
          </td>
        </tr>

        <!-- Booking Details Box -->
        <tr>
          <td style="padding:24px 40px;">
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#f9fafb;border-radius:12px;
                          border:1px solid #e5e7eb;overflow:hidden;">

              <!-- Box Header -->
              <tr>
                <td colspan="2"
                    style="background:#dcfce7;padding:14px 20px;
                           border-bottom:1px solid #bbf7d0;">
                  <strong style="color:#15803d;font-size:15px;">
                    📋 تفاصيل الحجز
                  </strong>
                </td>
              </tr>

              <!-- Row: Field -->
              <tr>
                <td style="padding:14px 20px;color:#6b7280;
                           font-size:14px;border-bottom:1px solid #f3f4f6;
                           width:40%;">🏟️ اسم الملعب</td>
                <td style="padding:14px 20px;color:#111827;font-weight:bold;
                           font-size:14px;border-bottom:1px solid #f3f4f6;">
                  ' . htmlspecialchars($d['field_name']) . '
                </td>
              </tr>

              <!-- Row: Location -->
              <tr style="background:#ffffff;">
                <td style="padding:14px 20px;color:#6b7280;
                           font-size:14px;border-bottom:1px solid #f3f4f6;">
                  📍 الموقع</td>
                <td style="padding:14px 20px;color:#111827;
                           font-size:14px;border-bottom:1px solid #f3f4f6;">
                  ' . htmlspecialchars($d['city']) . ' — ' . htmlspecialchars($d['address']) . '
                </td>
              </tr>

              <!-- Row: Date -->
              <tr>
                <td style="padding:14px 20px;color:#6b7280;
                           font-size:14px;border-bottom:1px solid #f3f4f6;">
                  📅 تاريخ الحجز</td>
                <td style="padding:14px 20px;color:#111827;font-weight:bold;
                           font-size:14px;border-bottom:1px solid #f3f4f6;">
                  ' . htmlspecialchars($d['booking_date']) . '
                </td>
              </tr>

              <!-- Row: Time -->
              <tr style="background:#ffffff;">
                <td style="padding:14px 20px;color:#6b7280;
                           font-size:14px;border-bottom:1px solid #f3f4f6;">
                  ⏰ الوقت</td>
                <td style="padding:14px 20px;color:#111827;
                           font-size:14px;border-bottom:1px solid #f3f4f6;">
                  ' . htmlspecialchars($d['start_time']) . ' — ' . htmlspecialchars($d['end_time']) . '
                  &nbsp;<span style="color:#6b7280;font-size:13px;">(' . $hours_label . ')</span>
                </td>
              </tr>

              <!-- Row: Payment -->
              <tr>
                <td style="padding:14px 20px;color:#6b7280;font-size:14px;
                           border-bottom:1px solid #f3f4f6;">
                  💳 طريقة الدفع</td>
                <td style="padding:14px 20px;color:#111827;
                           font-size:14px;border-bottom:1px solid #f3f4f6;">
                  ' . $payment_label . '
                </td>
              </tr>

              <!-- Row: Price (highlighted) -->
              <tr style="background:#f0fdf4;">
                <td style="padding:16px 20px;color:#15803d;
                           font-size:15px;font-weight:bold;">
                  💰 الإجمالي
                </td>
                <td style="padding:16px 20px;font-size:20px;
                           font-weight:bold;color:#16a34a;">
                  ' . number_format($d['total_price'], 0) . ' ج.س
                </td>
              </tr>

            </table>
          </td>
        </tr>

        <!-- Owner Contact -->
        <tr>
          <td style="padding:0 40px 24px;">
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#eff6ff;border-radius:12px;
                          border:1px solid #bfdbfe;padding:16px 20px;">
              <tr>
                <td>
                  <p style="margin:0 0 6px;color:#1d4ed8;font-weight:bold;font-size:14px;">
                    📞 للتواصل مع صاحب الملعب
                  </p>
                  <p style="margin:0;color:#374151;font-size:14px;">
                    ' . htmlspecialchars($d['owner_name']) . ' &nbsp;|&nbsp; ' . htmlspecialchars($d['owner_phone']) . '
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Notes (if any) -->
        ' . (!empty($d['customer_notes']) ? '
        <tr>
          <td style="padding:0 40px 24px;">
            <table width="100%" cellpadding="0" cellspacing="0"
                   style="background:#fffbeb;border-radius:12px;
                          border:1px solid #fde68a;padding:16px 20px;">
              <tr>
                <td>
                  <p style="margin:0 0 6px;color:#d97706;font-weight:bold;font-size:14px;">
                    📝 ملاحظاتك
                  </p>
                  <p style="margin:0;color:#374151;font-size:14px;">
                    ' . htmlspecialchars($d['customer_notes']) . '
                  </p>
                </td>
              </tr>
            </table>
          </td>
        </tr>' : '') . '

        <!-- CTA Button -->
        <tr>
          <td style="padding:0 40px 32px;text-align:center;">
            <a href="http://localhost/football-booking/customer/my_bookings.php"
               style="display:inline-block;background:linear-gradient(135deg,#16a34a,#15803d);
                      color:#ffffff;text-decoration:none;padding:14px 36px;
                      border-radius:10px;font-size:15px;font-weight:bold;">
              عرض حجوزاتي 📋
            </a>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#f9fafb;border-top:1px solid #e5e7eb;
                     padding:20px 40px;text-align:center;">
            <p style="color:#9ca3af;font-size:13px;margin:0;">
              تم إرسال هذا الإيميل تلقائياً من نظام ملاعب السودان<br>
              &copy; 2025 ملاعب السودان — جميع الحقوق محفوظة
            </p>
          </td>
        </tr>

      </table>
      <!-- End Card -->

    </td></tr>
  </table>

</body>
</html>';
}

// ── نسخة نص عادي (Fallback) ─────────────────────────────
function get_booking_email_text($d) {
    return "
تأكيد الحجز — ملاعب السودان
============================
مرحباً {$d['customer_name']}،

تم تأكيد حجزك بنجاح!

تفاصيل الحجز:
- الملعب: {$d['field_name']}
- الموقع: {$d['city']} — {$d['address']}
- التاريخ: {$d['booking_date']}
- الوقت: {$d['start_time']} — {$d['end_time']}
- الإجمالي: {$d['total_price']} ج.س
- طريقة الدفع: {$d['payment_method']}

للتواصل مع المالك: {$d['owner_name']} — {$d['owner_phone']}

شكراً لاستخدامك نظام ملاعب السودان!
";
}

// ── قالب إيميل الرفض ────────────────────────────────────
function get_rejection_email_html($d) {
    return '
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;direction:rtl;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:30px 0;">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,.1);overflow:hidden;">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#dc2626,#b91c1c);
                     padding:36px 40px;text-align:center;">
            <div style="font-size:48px;margin-bottom:10px;">❌</div>
            <h1 style="color:#fff;margin:0;font-size:24px;">اعتذار بخصوص طلب حجزك</h1>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="padding:32px 40px;">
            <p style="color:#374151;font-size:16px;">
              مرحباً <strong style="color:#dc2626;">' . htmlspecialchars($d['customer_name']) . '</strong>،
            </p>
            <p style="color:#6b7280;font-size:15px;line-height:1.7;">
              نأسف لإخبارك بأن طلب حجزك في ملعب
              <strong>' . htmlspecialchars($d['field_name']) . '</strong>
              بتاريخ <strong>' . htmlspecialchars($d['booking_date']) . '</strong>
              لم يتم قبوله هذه المرة.
            </p>
            <p style="color:#6b7280;font-size:15px;line-height:1.7;">
              يمكنك البحث عن ملاعب أخرى متاحة في نفس الوقت.
            </p>
            <div style="text-align:center;margin-top:24px;">
              <a href="http://localhost/football-booking/search.php"
                 style="display:inline-block;background:linear-gradient(135deg,#16a34a,#15803d);
                        color:#fff;text-decoration:none;padding:14px 36px;
                        border-radius:10px;font-size:15px;font-weight:bold;">
                ابحث عن ملعب آخر 🔍
              </a>
            </div>
          </td>
        </tr>

        <tr>
          <td style="background:#f9fafb;border-top:1px solid #e5e7eb;
                     padding:20px 40px;text-align:center;">
            <p style="color:#9ca3af;font-size:13px;margin:0;">
              &copy; 2025 ملاعب السودان
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';
}