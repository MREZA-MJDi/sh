<?php

namespace App\Enums;

enum CourseType: string
{
    case OnlineClass = 'online_class';
    case Recorded = 'recorded';
    case InPerson = 'in_person';

    public function label(): string
    {
        return match ($this) {
            self::OnlineClass => 'کلاس آنلاین (زنده)',
            self::Recorded => 'دوره ضبط‌شده',
            self::InPerson => 'کلاس حضوری',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OnlineClass => '#dc2626',
            self::Recorded => '#2563eb',
            self::InPerson => '#16a34a',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::OnlineClass => '🔴',
            self::Recorded => '▶️',
            self::InPerson => '🏫',
        };
    }

    public function hint(): string
    {
        return match ($this) {
            self::OnlineClass => 'زنده با معلم؛ در زمان کلاس از همین صفحه وارد می‌شوید',
            self::Recorded => 'هر زمان که بخواهید، با پلیر SpotPlayer',
            self::InPerson => 'حضوری در محل برگزاری',
        };
    }
}
