<?php
// توابع کمکی — در پروژه‌ی واقعی به app/helpers.php منتقل و در composer.json (autoload.files) ثبت شود.
if (! function_exists('fa')) {
    /** تبدیل ارقام لاتین به فارسی */
    function fa($value): string
    {
        return strtr((string) $value, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }
}
if (! function_exists('toman')) {
    function toman($n): string { return fa(number_format((int) $n)) . ' تومان'; }
}
