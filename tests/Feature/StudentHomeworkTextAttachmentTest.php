<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Homework;
use App\Models\HomeworkSubmission;
use App\Models\HomeworkSubmissionFile;
use App\Models\Role;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class StudentHomeworkTextAttachmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_student_can_submit_a_plain_text_homework_attachment(): void
    {
        $student = $this->userWithRole('student');
        $teacher = $this->userWithRole('teacher');

        $course = Course::query()->create([
            'title' => 'دوره تکلیف متنی',
            'slug' => 'plain-text-homework-course',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس تکلیف متنی',
            'status' => 'active',
        ]);
        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        $homework = Homework::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'تمرین پاسخ متنی',
            'instructions' => 'پاسخ را در فایل متنی ارسال کنید.',
            'status' => 'published',
            'created_by' => $teacher->id,
        ]);

        $this->actingAs($student)
            ->post(route('student.homework.submissions.store', $homework), [
                'answer' => null,
                'files' => [
                    UploadedFile::fake()->create('answer.txt', 2, 'text/plain'),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'پاسخ تکلیف با موفقیت ثبت شد.');

        $submission = HomeworkSubmission::query()
            ->where('homework_id', $homework->id)
            ->where('student_id', $student->id)
            ->firstOrFail();

        $this->assertSame('submitted', $submission->status);

        $attachment = HomeworkSubmissionFile::query()
            ->where('submission_id', $submission->id)
            ->firstOrFail();

        $this->assertSame('answer.txt', $attachment->original_name);
        Storage::disk('local')->assertExists($attachment->path);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }
}
