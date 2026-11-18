<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\StudentNote;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StudentNotesWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_student_can_view_notes_page(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        StudentNote::query()->create([
            'student_id' => $student->id,
            'body' => 'یادداشت تستی اول',
            'tone' => '',
        ]);

        $this->actingAs($student)
            ->get(route('student.notes.index'))
            ->assertOk()
            ->assertSee('یادداشت‌ها')
            ->assertSee('یادداشت تستی اول');
    }

    public function test_student_can_create_note_with_default_tone_without_validation_error(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $response = $this->actingAs($student)
            ->from(route('student.notes.index'))
            ->post(route('student.notes.store'), [
                'body' => 'خلاصه نکات فصل سوم زیست شناسی',
                'tone' => 'default',
            ]);

        $response->assertRedirect(route('student.notes.index'))
            ->assertSessionHas('status', 'یادداشت با موفقیت ذخیره شد.')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('student_notes', [
            'student_id' => $student->id,
            'body' => 'خلاصه نکات فصل سوم زیست شناسی',
        ]);
    }

    public function test_student_can_delete_own_note(): void
    {
        $student = User::factory()->create();
        $student->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $note = StudentNote::query()->create([
            'student_id' => $student->id,
            'body' => 'یادداشت برای حذف',
            'tone' => '',
        ]);

        $this->actingAs($student)
            ->from(route('student.notes.index'))
            ->delete(route('student.notes.destroy', $note))
            ->assertRedirect(route('student.notes.index'))
            ->assertSessionHas('status', 'یادداشت حذف شد.');

        $this->assertDatabaseMissing('student_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_student_cannot_delete_another_students_note(): void
    {
        $student1 = User::factory()->create();
        $student1->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $student2 = User::factory()->create();
        $student2->roles()->attach(Role::query()->where('name', 'student')->value('id'));

        $note = StudentNote::query()->create([
            'student_id' => $student2->id,
            'body' => 'یادداشت دانش‌آموز دیگر',
            'tone' => '',
        ]);

        $this->actingAs($student1)
            ->delete(route('student.notes.destroy', $note))
            ->assertNotFound();

        $this->assertDatabaseHas('student_notes', [
            'id' => $note->id,
        ]);
    }
}
