<?php

namespace App\\Support;

use Carbon\\CarbonImmutable;
use Carbon\\CarbonInterface;
use Illuminate\\Support\\Collection;

final class JalaliCalendar
{
    /**
     * Build a Saturday-first Persian month grid with lightweight session markers.
     *
     * @param iterable<object> $sessions Sessions must expose starts_at and status.
     * @return array<string, mixed>
     */
    public static function make(CarbonImmutable $selectedDate, iterable $sessions): array
    {
        $parts = JalaliDate::fromGregorian($selectedDate);
        $year = $parts['year'];
        $month = $parts['month'];
        $firstDay = JalaliDate::toGregorian($year, $month, 1);
        $dayCount = JalaliDate::daysInMonth($year, $month);
        $sessionGroups = collect($sessions)->groupBy(
            fn ($session): string => $session->starts_at->setTimezone(config('app.timezone', 'Asia/Tehran'))->toDateString()
        );

        $days = [];
        for ($day = 1; $day <= $dayCount; $day++) {
            $date = JalaliDate::toGregorian($year, $month, $day);
            $items = $sessionGroups->get($date->toDateString(), collect());

            $days[] = [
                'day' => PersianDate::toPersianDigits($day),
                'date' => $date->toDateString(),
                'sessions' => $items->count(),
                'scheduled' => $items->where('status', 'scheduled')->count(),
                'completed' => $items->where('status', 'completed')->count(),
                'selected' => $date->isSameDay($selectedDate),
                'today' => $date->isSameDay(CarbonImmutable::now(config('app.timezone', 'Asia/Tehran'))),
            ];
        }

        $previousYear = $month === 1 ? $year - 1 : $year;
        $previousMonth = $month === 1 ? 12 : $month - 1;
        $nextYear = $month === 12 ? $year + 1 : $year;
        $nextMonth = $month === 12 ? 1 : $month + 1;

        return [
            'label' => PersianDate::format($firstDay, 'F Y'),
            'year' => $year,
            'month' => $month,
            'leading_blanks' => ($firstDay->dayOfWeek + 1) % 7,
            'weekdays' => ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'],
            'days' => $days,
            'selected_date' => $selectedDate->toDateString(),
            'previous_date' => JalaliDate::toGregorian($previousYear, $previousMonth, 1)->toDateString(),
            'next_date' => JalaliDate::toGregorian($nextYear, $nextMonth, 1)->toDateString(),
        ];
    }
}
