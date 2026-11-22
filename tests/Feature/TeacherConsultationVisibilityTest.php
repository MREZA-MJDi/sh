<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Consultation;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ConsultationRequestNotification;
use Illuminate\Support\Facades\Notification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TeacherConsultationVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_sees_parent_consultations_for_assigned_students_but_not_other_classes(): void
    {
        $teacher = $this->userWithRole('teacher');
        $otherTeacher = $this->userWithRole('teacher');
        $guardian = $this->userWithRole('parent');
        $student = $this->userWithRole('student');
        $otherStudent = $this->userWithRole('student');

        $course = Course::query()->create([
            'title' => 'دوره مشاوره', 'slug' => 'consultation-visibility-course',
            'status' => 'published', 'type' => 'free', 'price' => 0,
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس تحت مسئولیت مدرس',
            'status' => 'active',
        ]);
        $otherClassroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $otherTeacher->id,
            'name' => 'کلاس مدرس دیگر',
            'status' => 'active',
        ]);

        $classroom->students()->attach($student->id);
        $otherClassroom->students()->attach($otherStudent->id);
        $guardian->children()->attach([$student->id, $otherStudent->id]);

        Consultation::query()->create([
            'requester_id' => $guardian->id,
            'student_id' => $student->id,
            'type' => 'ticket',
            'topic' => 'مشاوره قابل مشاهده برای مدرس',
            'notes' => 'درخواست والدین برای بررسی وضعیت آموزشی',
            'status' => 'pending',
        ]);
        Consultation::query()->create([
            'requester_id' => $guardian->id,
            'student_id' => $otherStudent->id,
            'type' => 'ticket',
            'topic' => 'مشاوره خارج از کلاس مدرس',
            'notes' => 'این درخواست نباید نمایش داده شود',
            'status' => 'pending',
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.consultations.index'))
            ->assertOk()
            ->assertSee('مشاوره قابل مشاهده برای مدرس')
            ->assertSee($guardian->name)
            ->assertSee($student->name)
            ->assertDontSee('مشاوره خارج از کلاس مدرس');
    }

    public function test_administrator_can_view_parent_consultations_across_all_classes(): void
    {
        $admin = $this->userWithRole('admin');
        $guardian = $this->userWithRole('parent');
        $student = $this->userWithRole('student');

        Consultation::query()->create([
            'requester_id' => $guardian->id,
            'student_id' => $student->id,
            'type' => 'phone',
            'topic' => 'درخواست مشاوره سراسری',
            'notes' => 'درخواست برای بررسی مدیریت',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.consultations.index'))
            ->assertOk()
            ->assertSee('درخواست مشاوره سراسری')
            ->assertSee($guardian->name)
            ->assertSee($student->name);
    }


    public function test_parent_consultation_notifies_administrators_and_the_assigned_teacher(): void
    {
        Notification::fake();

        $admin = $this->userWithRole('admin');
        $teacher = $this->userWithRole('teacher');
        $guardian = $this->userWithRole('parent');
        $student = $this->userWithRole('student');
        $guardian->children()->attach($student);

        $classroom = Classroom::query()->create([
            'teacher_id' => $teacher->id,
            'name' => 'کلاس اعلان مشاوره',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);

        $this->actingAs($guardian)
            ->post(route('guardian.consultations.store'), [
                'student_id' => $student->id,
                'type' => 'ticket',
                'topic' => 'درخواست مشاوره والدین',
                'body' => 'لطفاً وضعیت آموزشی فرزندم بررسی شود.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('consultations', [
            'requester_id' => $guardian->id,
            'student_id' => $student->id,
            'topic' => 'درخواست مشاوره والدین',
            'status' => 'pending',
        ]);

        Notification::assertSentTo($admin, ConsultationRequestNotification::class);
        Notification::assertSentTo($teacher, ConsultationRequestNotification::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }
}
