<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

final class JalaliDate
{
    /**
     * Convert a Gregorian date to [year, month, day] in the Persian calendar.
     *
     * @return array{year:int,month:int,day:int}
     */
    public static function fromGregorian(CarbonInterface $date): array
    {
        $gDays = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $jDays = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

        $gy = $date->year - 1600;
        $gm = $date->month - 1;
        $gd = $date->day - 1;

        $gDayNo = 365 * $gy
            + intdiv($gy + 3, 4)
            - intdiv($gy + 99, 100)
            + intdiv($gy + 399, 400);

        for ($index = 0; $index < $gm; $index++) {
            $gDayNo += $gDays[$index];
        }

        $isGregorianLeap = ($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0;
        if ($gm > 1 && $isGregorianLeap) {
            $gDayNo++;
        }

        $gDayNo += $gd;
        $jDayNo = $gDayNo - 79;
        $jNp = intdiv($jDayNo, 12053);
        $jDayNo %= 12053;

        $jy = 979 + 33 * $jNp + 4 * intdiv($jDayNo, 1461);
        $jDayNo %= 1461;

        if ($jDayNo >= 366) {
            $jy += intdiv($jDayNo - 1, 365);
            $jDayNo = ($jDayNo - 1) % 365;
        }

        $monthIndex = 0;
        while ($monthIndex < 11 && $jDayNo >= $jDays[$monthIndex]) {
            $jDayNo -= $jDays[$monthIndex];
            $monthIndex++;
        }

        return ['year' => $jy, 'month' => $monthIndex + 1, 'day' => $jDayNo + 1];
    }

    public static function toGregorian(int $year, int $month, int $day): CarbonImmutable
    {
        if ($month < 1 || $month > 12 || $day < 1 || $day > self::daysInMonth($year, $month)) {
            throw new \InvalidArgumentException('تاریخ شمسی معتبر نیست.');
        }

        $jy = $year - 979;
        $jDayNo = 365 * $jy
            + intdiv($jy, 33) * 8
            + intdiv(($jy % 33) + 3, 4);

        $jDayNo += $month < 7 ? ($month - 1) * 31 : (($month - 7) * 30) + 186;
        $jDayNo += $day - 1;

        $gDayNo = $jDayNo + 79;
        $gy = 1600 + 400 * intdiv($gDayNo, 146097);
        $gDayNo %= 146097;

        $leap = true;
        if ($gDayNo >= 36525) {
            $gDayNo--;
            $gy += 100 * intdiv($gDayNo, 36524);
            $gDayNo %= 36524;

            if ($gDayNo >= 365) {
                $gDayNo++;
            } else {
                $leap = false;
            }
        }

        $gy += 4 * intdiv($gDayNo, 1461);
        $gDayNo %= 1461;

        if ($gDayNo >= 366) {
            $leap = false;
            $gDayNo--;
            $gy += intdiv($gDayNo, 365);
            $gDayNo %= 365;
        }

        $monthDays = [31, $leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $gregorianMonth = 1;
        while ($gregorianMonth <= 12 && $gDayNo >= $monthDays[$gregorianMonth - 1]) {
            $gDayNo -= $monthDays[$gregorianMonth - 1];
            $gregorianMonth++;
        }

        return CarbonImmutable::create($gy, $gregorianMonth, $gDayNo + 1, 0, 0, 0, config('app.timezone', 'Asia/Tehran'));
    }

    public static function daysInMonth(int $year, int $month): int
    {
        if ($month >= 1 && $month <= 6) return 31;
        if ($month >= 7 && $month <= 11) return 30;
        if ($month !== 12) throw new \InvalidArgumentException('ماه شمسی معتبر نیست.');

        $newYear = self::toGregorian($year, 1, 1);
        $nextNewYear = self::toGregorian($year + 1, 1, 1);

        return (int) $newYear->diffInDays($nextNewYear) === 366 ? 30 : 29;
    }

    public static function format(CarbonInterface $date, bool $withTime = false): string
    {
        $parts = self::fromGregorian($date);
        $formatted = sprintf('%04d/%02d/%02d', $parts['year'], $parts['month'], $parts['day']);

        return $withTime ? $formatted.' '.$date->format('H:i') : $formatted;
    }

    public static function monthLabel(int $year, int $month): string
    {
        return sprintf('%04d/%02d', $year, $month);
    }

    public static function monthKey(int $year, int $month): string
    {
        return sprintf('%04d-%02d', $year, $month);
    }

    public static function monthKeyByOffset(int $year, int $month, int $monthsBack = 0): string
    {
        $absoluteMonth = ($year * 12) + ($month - 1) - $monthsBack;
        $targetYear = intdiv($absoluteMonth, 12);
        $targetMonth = ($absoluteMonth % 12) + 1;

        return self::monthKey($targetYear, $targetMonth);
    }
}
