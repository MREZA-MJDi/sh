<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherMessageWorkspaceScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_sees_messages_for_students_in_assigned_classes_even_when_teacher_is_recipient(): void
    {
        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $classroom = Classroom::query()->create([
            'teacher_id' => $teacher->id,
            'name' => 'کلاس پیام مدرس',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $guardian = User::factory()->create();
        $guardian->roles()->attach(Role::query()->where('name', 'parent')->value('id'));
        $guardian->children()->attach($student->id);

        $message = Message::query()->create([
            'sender_id' => $guardian->id,
            'recipient_id' => $teacher->id,
            'student_id' => $student->id,
            'subject' => 'پیام درباره وضعیت دانش‌آموز',
            'body' => 'لطفاً وضعیت آموزشی را بررسی کنید.',
            'status' => 'open',
        ]);

        $otherStudent = User::factory()->create();
        $otherStudent->roles()->attach(Role::query()->where('name', 'student')->value('id'));
        $otherGuardian = User::factory()->create();
        $otherGuardian->roles()->attach(Role::query()->where('name', 'parent')->value('id'));
        $otherGuardian->children()->attach($otherStudent->id);

        $otherMessage = Message::query()->create([
            'sender_id' => $otherGuardian->id,
            'recipient_id' => $teacher->id,
            'student_id' => $otherStudent->id,
            'subject' => 'پیام خارج از کلاس',
            'body' => 'این پیام نباید نمایش داده شود.',
            'status' => 'open',
        ]);

        $this->actingAs($teacher)
            ->get(route('admin.messages.index'))
            ->assertOk()
            ->assertSee('پیام درباره وضعیت دانش‌آموز')
            ->assertDontSee('پیام خارج از کلاس');
    }
}
