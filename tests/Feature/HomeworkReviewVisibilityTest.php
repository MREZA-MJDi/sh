<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\Role;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HomeworkReviewVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_student_and_guardian_see_review_score_and_feedback(): void
    {
        $student = $this->userWithRole('student');
        $guardian = $this->userWithRole('parent');
        $guardian->children()->attach($student->id);
        $teacher = $this->userWithRole('teacher');

        $course = Course::query()->create([
            'title' => 'دوره تکلیف',
            'slug' => 'homework-review-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);
        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس تکلیف',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $homework = Homework::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'تمرین فیزیک',
            'instructions' => 'تمرین‌های فصل اول',
            'status' => 'published',
            'created_by' => $teacher->id,
        ]);
        HomeworkSubmission::query()->create([
            'homework_id' => $homework->id,
            'student_id' => $student->id,
            'answer' => 'پاسخ من',
            'score' => 18,
            'feedback' => 'بازخورد نهایی مدرس برای این تکلیف',
            'status' => 'reviewed',
            'submitted_at' => now()->subDay(),
            'reviewed_by' => $teacher->id,
            'reviewed_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertSee('بازخورد نهایی مدرس برای این تکلیف')
            ->assertSee('نتیجه بررسی مدرس');

        $this->actingAs($guardian)
            ->get(route('guardian.dashboard'))
            ->assertOk()
            ->assertSee('نتایج بررسی تکالیف')
            ->assertSee('بازخورد نهایی مدرس برای این تکلیف')
            ->assertSee('تمرین فیزیک');
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }
}
