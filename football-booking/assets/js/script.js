/**
 * نظام حجز ملاعب كرة القدم
 * assets/js/script.js
 */

$(document).ready(function () {

    // ─────────────────────────────────────────────
    // 🌙 Dark Mode Toggle
    // ─────────────────────────────────────────────
    const DARK_KEY = 'darkMode';
    const $body    = $('body');
    const $toggle  = $('#darkToggle');

    // تطبيق الحالة المحفوظة
    if (localStorage.getItem(DARK_KEY) === 'true') {
        $body.addClass('dark-mode');
    }

    // زر التبديل
    $toggle.on('click', function () {
        $body.toggleClass('dark-mode');
        const isDark = $body.hasClass('dark-mode');
        localStorage.setItem(DARK_KEY, isDark);
    });

    // ─────────────────────────────────────────────
    // ⏱️ إخفاء الإشعارات تلقائياً بعد 5 ثواني
    // ─────────────────────────────────────────────
    setTimeout(function () {
        $('.alert-dismissible').fadeOut('slow');
    }, 5000);

    // ─────────────────────────────────────────────
    // 🗑️ تأكيد الحذف
    // ─────────────────────────────────────────────
    $('.btn-delete, [data-confirm]').on('click', function (e) {
        const msg = $(this).data('confirm') || 'هل أنت متأكد من هذا الإجراء؟';
        if (!confirm(msg)) e.preventDefault();
    });

    // ─────────────────────────────────────────────
    // 📅 التحقق من نموذج الحجز
    // ─────────────────────────────────────────────
    $('#bookingForm').on('submit', function (e) {
        const start = $('#start_time').val();
        const end   = $('#end_time').val();
        if (start && end && start >= end) {
            showToast('وقت الانتهاء يجب أن يكون بعد وقت البداية', 'danger');
            e.preventDefault();
        }
    });

    // حساب السعر عند تغيير الأوقات
    $('#start_time, #end_time').on('change', calculateTotalPrice);

    // ─────────────────────────────────────────────
    // 🔑 تحقق من تطابق كلمة المرور
    // ─────────────────────────────────────────────
    $('#confirm_password').on('keyup', function () {
        const pass    = $('#password').val();
        const confirm = $(this).val();
        const $err    = $('#passwordError');

        if (!confirm) {
            $(this).removeClass('is-valid is-invalid');
            $err.text('');
            return;
        }

        if (pass !== confirm) {
            $(this).addClass('is-invalid').removeClass('is-valid');
            $err.text('كلمات المرور غير متطابقة').css('color', '#dc2626');
        } else {
            $(this).addClass('is-valid').removeClass('is-invalid');
            $err.text('✓ كلمات المرور متطابقة').css('color', '#16a34a');
        }
    });

    // ─────────────────────────────────────────────
    // 🖼️ معاينة صورة الملعب
    // ─────────────────────────────────────────────
    $('#field_image').on('change', function () {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            $('#imagePreview').html(
                `<img src="${e.target.result}" class="img-thumbnail mt-2"
                      style="max-width:280px; border-radius:10px;">`
            );
        };
        reader.readAsDataURL(file);
    });

    // ─────────────────────────────────────────────
    // 🔍 بحث سريع في الجداول
    // ─────────────────────────────────────────────
    $('#tableSearch').on('keyup', function () {
        const val = $(this).val().toLowerCase();
        $('#dataTable tbody tr').filter(function () {
            $(this).toggle($(this).text().toLowerCase().includes(val));
        });
    });

    // ─────────────────────────────────────────────
    // ⬆️ Scroll to Top
    // ─────────────────────────────────────────────
    $(window).on('scroll', function () {
        $('#scrollTop').toggle($(this).scrollTop() > 300);
    });
    $('#scrollTop').on('click', function () {
        $('html, body').animate({ scrollTop: 0 }, 600, 'swing');
    });

    // ─────────────────────────────────────────────
    // 💳 إظهار/إخفاء حقول البطاقة
    // ─────────────────────────────────────────────
    $('#payment_method').on('change', function () {
        const isCard = this.value === 'card';
        if (isCard) {
            $('#cardFields').slideDown(300);
        } else {
            $('#cardFields').slideUp(300);
        }
        $('#card_number, #card_holder').prop('required', isCard);
    });

    // تنسيق رقم البطاقة (XXXX XXXX XXXX XXXX)
    $(document).on('input', '#card_number', function () {
        let v       = this.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let matches = v.match(/\d{4,16}/g);
        let match   = (matches && matches[0]) || '';
        let parts   = [];
        for (let i = 0, len = match.length; i < len; i += 4)
            parts.push(match.substring(i, i + 4));
        this.value = parts.length ? parts.join(' ') : v;
    });

    // تنسيق تاريخ انتهاء البطاقة (MM/YY)
    $(document).on('input', '.card-expiry', function () {
        let v = this.value.replace(/\D/g, '').substring(0, 4);
        if (v.length >= 2) v = v.substring(0, 2) + '/' + v.substring(2);
        this.value = v;
    });

    // Tooltips
    $('[data-toggle="tooltip"]').tooltip();

});

// ─────────────────────────────────────────────
// 💰 حساب السعر الإجمالي
// ─────────────────────────────────────────────
function calculateTotalPrice() {
    const start        = $('#start_time').val();
    const end          = $('#end_time').val();
    const pricePerHour = parseFloat($('#price_per_hour').val()) || 0;
    const $display     = $('#priceDisplay');

    if (!start || !end) return;

    const startDate = new Date('2000-01-01 ' + start);
    const endDate   = new Date('2000-01-01 ' + end);

    if (endDate <= startDate) {
        $display
            .attr('class', 'alert alert-danger')
            .html('<i class="fas fa-exclamation-circle"></i> وقت الانتهاء يجب أن يكون بعد وقت البداية');
        return;
    }

    const hours      = (endDate - startDate) / 3600000;
    const totalPrice = (hours * pricePerHour).toFixed(0);

    $('#total_hours').val(hours);
    $('#total_price').val(totalPrice);

    $display
        .attr('class', 'alert alert-success')
        .html(
            `<i class="fas fa-check-circle"></i>
             <strong>${hours} ساعة × ${pricePerHour.toLocaleString()} = ${parseInt(totalPrice).toLocaleString()} جنيه</strong>`
        );
}

// ─────────────────────────────────────────────
// 🔔 Toast Notification
// ─────────────────────────────────────────────
function showToast(message, type = 'success') {
    const icons = { success: '✅', danger: '❌', warning: '⚠️', info: 'ℹ️' };
    const $toast = $(`
        <div class="alert alert-${type} alert-dismissible"
             style="position:fixed; top:80px; left:50%; transform:translateX(-50%);
                    z-index:9999; min-width:300px; text-align:center;
                    border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,.2);
                    animation:fadeIn .3s ease;">
            ${icons[type] || ''} ${message}
        </div>
    `);
    $('body').append($toast);
    setTimeout(() => $toast.fadeOut(400, () => $toast.remove()), 3000);
}

// ─────────────────────────────────────────────
// 📊 تحديث حالة الحجز بـ AJAX
// ─────────────────────────────────────────────
function updateBookingStatus(bookingId, status) {
    if (!confirm('هل أنت متأكد من تغيير حالة الحجز؟')) return;
    $.ajax({
        url: 'update_booking_status.php',
        method: 'POST',
        data: { booking_id: bookingId, status: status },
        success(res) {
            if (res.success) {
                showToast('تم التحديث بنجاح');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('خطأ: ' + res.message, 'danger');
            }
        },
        error() { showToast('خطأ في الاتصال', 'danger'); }
    });
}

// ─────────────────────────────────────────────
// 📤 تصدير الجدول إلى CSV
// ─────────────────────────────────────────────
function exportTableToCSV(filename) {
    const rows = document.querySelectorAll('table tr');
    const csv  = [...rows].map(r =>
        [...r.querySelectorAll('td,th')]
            .map(c => `"${c.innerText.replace(/"/g, '""')}"`)
            .join(',')
    ).join('\n');

    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href  = URL.createObjectURL(blob);
    link.download = filename || 'export.csv';
    link.click();
}

// ─────────────────────────────────────────────
// 🖨️ طباعة الصفحة
// ─────────────────────────────────────────────
function printPage() { window.print(); }

// ─────────────────────────────────────────────
// 🔽 فلترة الحجوزات في صفحة حجوزاتي
// ─────────────────────────────────────────────
function filterBookings(status) {
    document.querySelectorAll('.booking-item').forEach(el => {
        el.style.display = (status === 'all' || el.dataset.status === status) ? 'block' : 'none';
    });
    document.querySelectorAll('.btn-group button').forEach(b => b.classList.remove('active'));
    if (event && event.target) event.target.classList.add('active');
}