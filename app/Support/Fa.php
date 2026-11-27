<?php

namespace App\Support;

use DateTimeInterface;

/** Persian display helpers. Money is stored in toman. */
class Fa
{
    public static function num(int|float|string|null $v): string
    {
        return PersianDate::toPersianDigits(number_format((float) ($v ?? 0)));
    }

    public static function toman(int|float|string|null $v): string
    {
        return self::num($v) . ' تومان';
    }

    public static function rial(int|float|string|null $v): string
    {
        return self::num(((float) ($v ?? 0)) * 10) . ' ریال';
    }

    public static function date(DateTimeInterface|string|null $v, bool $time = false): string
    {
        return PersianDate::format($v, $time ? 'Y/m/d H:i' : 'Y/m/d');
    }
}
