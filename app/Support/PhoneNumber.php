<?php

namespace App\Support;

use Illuminate\Support\HtmlString;

final class PhoneNumber
{
    private const TO_ASCII = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    private const TO_PERSIAN = [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ];

    public static function format(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        $ascii = strtr($value, self::TO_ASCII);
        $compact = preg_replace('/[\s().-]+/u', '', $ascii) ?? $ascii;

        if (preg_match('/^(09)(\d{2})(\d{3})(\d{4})$/', $compact, $match) === 1) {
            $formatted = $match[1].$match[2].' '.$match[3].' '.$match[4];
        } elseif (preg_match('/^(?:\+98|0098|98)(9\d{2})(\d{3})(\d{4})$/', $compact, $match) === 1) {
            $formatted = '+98 '.$match[1].' '.$match[2].' '.$match[3];
        } else {
            $formatted = $ascii;
        }

        return strtr($formatted, self::TO_PERSIAN);
    }

    public static function html(?string $value): HtmlString
    {
        return new HtmlString(
            '<bdi class="phone-number" dir="ltr">'.e(self::format($value)).'</bdi>'
        );
    }
}
