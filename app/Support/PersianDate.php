<?php

namespace App\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

final class PersianDate
{
    private const MONTHS = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    ];

    private const WEEKDAYS = [
        0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه', 3 => 'چهارشنبه',
        4 => 'پنجشنبه', 5 => 'جمعه', 6 => 'شنبه',
    ];

    private const DIGITS = [
        '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴',
        '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹',
    ];

    public static function format(
        DateTimeInterface|string|null $value,
        string $pattern = 'Y/m/d H:i'
    ): string {
        if ($value === null || $value === '') {
            return '—';
        }

        $timezone = new DateTimeZone((string) config('app.timezone', 'Asia/Tehran'));

        try {
            $date = $value instanceof DateTimeInterface
                ? DateTimeImmutable::createFromInterface($value)->setTimezone($timezone)
                : (new DateTimeImmutable($value, $timezone))->setTimezone($timezone);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Invalid date value supplied to PersianDate.', previous: $exception);
        }

        ['year' => $year, 'month' => $month, 'day' => $day] = JalaliDate::fromGregorian(
            \Carbon\CarbonImmutable::instance($date)
        );

        $tokens = [
            'Y' => sprintf('%04d', $year),
            'y' => substr((string) $year, -2),
            'm' => sprintf('%02d', $month),
            'n' => (string) $month,
            'd' => sprintf('%02d', $day),
            'j' => (string) $day,
            'F' => self::MONTHS[$month],
            'M' => mb_substr(self::MONTHS[$month], 0, 3),
            'l' => self::WEEKDAYS[(int) $date->format('w')],
            'D' => mb_substr(self::WEEKDAYS[(int) $date->format('w')], 0, 3),
        ];

        $formatted = preg_replace_callback(
            '/\\\\?([a-zA-Z])/',
            static function (array $matches) use ($date, $tokens): string {
                if (($matches[0][0] ?? '') === '\\') {
                    return $matches[1];
                }

                return $tokens[$matches[1]] ?? $date->format($matches[1]);
            },
            $pattern
        ) ?? $pattern;

        return self::toPersianDigits($formatted);
    }

    public static function time(?string $value): string
    {
        if ($value === null || ! preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', trim($value))) {
            return '—';
        }

        return self::toPersianDigits(trim($value));
    }

    public static function toPersianDigits(string|int|float $value): string
    {
        return strtr((string) $value, self::DIGITS);
    }

    public static function weekdayName(string $day): string
    {
        $names = [
            'sat' => 'شنبه',
            'sun' => 'یکشنبه',
            'mon' => 'دوشنبه',
            'tue' => 'سه‌شنبه',
            'wed' => 'چهارشنبه',
            'thu' => 'پنجشنبه',
            'fri' => 'جمعه',
        ];

        return $names[strtolower($day)] ?? '—';
    }
}
