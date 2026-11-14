<?php

namespace App\Services\Learning;

use App\Models\Course;
use App\Models\User;
use Skyroom\Laravel\Facades\Skyroom; // composer require eamirgh/skyroom-laravel (پکیج جامعه؛ خودت بررسی‌اش کن)

/** نام و نوع پارامترها را با مستندات رسمی وب‌سرویس اسکای‌روم تطبیق بده. */
class LiveClassService
{
    public function ensureRoom(Course $course): string
    {
        if ($course->skyroom_room_id) {
            return (string) $course->skyroom_room_id;
        }

        $id = Skyroom::rooms()->create([
            'name' => 'course-' . $course->id,
            'title' => $course->title ?? $course->name,
            'guest_login' => false,
            'op_login_first' => true,
        ]);

        $course->update(['skyroom_room_id' => $id]);

        return (string) $id;
    }

    public function joinUrl(User $user, Course $course): string
    {
        return Skyroom::createLoginUrl([
            'room_id' => (int) $this->ensureRoom($course),
            'user_id' => 'u' . $user->id,
            'nickname' => "{$user->name} (#{$user->id})", // شناسه داخل نام، برای تطبیق گزارش حضور و غیاب
            'language' => 'fa',
            'ttl' => 120,
        ]);
    }
}
