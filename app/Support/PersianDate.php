<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use IntlDateFormatter;

final class PersianDate
{
    private const DIGITS = [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ];

    private const WEEKDAYS = [
        'sat' => 'شنبه',
        'sun' => 'یکشنبه',
        'mon' => 'دوشنبه',
        'tue' => 'سه‌شنبه',
        'wed' => 'چهارشنبه',
        'thu' => 'پنجشنبه',
        'fri' => 'جمعه',
    ];

    public static function format(
        DateTimeInterface|string|null $value,
        string $pattern = 'yyyy/MM/dd HH:mm'
    ): string {
        if ($value === null || $value === '') {
            return '—';
        }

        $timezone = new DateTimeZone((string) config('app.timezone', 'Asia/Tehran'));
        $date = self::toDateTime($value)->setTimezone($timezone);

        if (! class_exists(IntlDateFormatter::class)) {
            return self::formatGregorianFallback($date, $pattern);
        }

        $formatter = new IntlDateFormatter(
            'fa_IR@calendar=persian',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            $timezone->getName(),
            IntlDateFormatter::TRADITIONAL
        );
        $formatter->setPattern($pattern);

        $formatted = $formatter->format($date);
        if ($formatted === false) {
            return self::formatGregorianFallback($date, $pattern);
        }

        return strtr($formatted, self::DIGITS);
    }

    public static function time(?string $value): string
    {
        if ($value === null || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', trim($value))) {
            return '—';
        }

        return strtr(trim($value), self::DIGITS);
    }

    public static function weekdayName(string $value): string
    {
        return self::WEEKDAYS[strtolower($value)] ?? '—';
    }

    private static function toDateTime(DateTimeInterface|string $value): DateTimeImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        return new DateTimeImmutable(
            $value,
            new DateTimeZone((string) config('app.timezone', 'Asia/Tehran'))
        );
    }

    private static function formatGregorianFallback(DateTimeImmutable $date, string $pattern): string
    {
        $phpPattern = strtr($pattern, [
            'yyyy' => 'Y',
            'MM' => 'm',
            'dd' => 'd',
            'HH' => 'H',
            'mm' => 'i',
        ]);

        return strtr($date->format($phpPattern), self::DIGITS);
    }
}
