<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Course;
use App\Models\Role;
use App\Models\SpotPlayerVideo;
use App\Models\User;
use App\Services\Courses\CourseEnrollmentService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class StudentVideoAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_active_course_and_class_members_can_see_recording_but_raw_file_is_never_streamed(): void
    {
        $student = $this->userWithRole('student');
        $teacher = $this->userWithRole('teacher');
        [$course, $classroom] = $this->courseAndClassroom($teacher, 'video-member-course');

        $classroom->students()->attach($student->id);
        app(CourseEnrollmentService::class)->syncClassroomRoster($classroom);

        Storage::disk('local')->put('private/spotplayer-staging/lesson.mp4', 'video-data');
        $video = SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'ضبط جلسه فیزیک',
            'status' => 'waiting_contract',
            'file_path' => 'private/spotplayer-staging/lesson.mp4',
            'file_original_name' => 'lesson.mp4',
            'file_mime_type' => 'video/mp4',
            'file_size_bytes' => 10,
            'file_sha256' => hash('sha256', 'video-data'),
            'staged_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.videos.index'))
            ->assertOk()
            ->assertSee('ضبط جلسه فیزیک');

        $this->get(route('student.videos.show', $video))
            ->assertOk()
            ->assertSee('پخش محافظت‌شده هنوز آماده نیست')
            ->assertSee('SpotPlayer');

        $this->get(route('student.videos.stream', $video))
            ->assertStatus(409)
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertDontSee('video-data');
    }

    public function test_video_is_not_visible_to_students_outside_its_course_or_class(): void
    {
        $student = $this->userWithRole('student');
        $teacher = $this->userWithRole('teacher');
        [$course, $classroom] = $this->courseAndClassroom($teacher, 'video-private-course');

        Storage::disk('local')->put('private/spotplayer-staging/private.mp4', 'private-video');
        $video = SpotPlayerVideo::query()->create([
            'course_id' => $course->id,
            'classroom_id' => $classroom->id,
            'created_by' => $teacher->id,
            'title' => 'ضبط خصوصی کلاس',
            'status' => 'waiting_contract',
            'file_path' => 'private/spotplayer-staging/private.mp4',
            'file_original_name' => 'private.mp4',
            'file_mime_type' => 'video/mp4',
            'file_size_bytes' => 13,
            'file_sha256' => hash('sha256', 'private-video'),
            'staged_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.videos.show', $video))
            ->assertNotFound();

        $this->get(route('student.videos.stream', $video))
            ->assertNotFound();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }

    /** @return array{0: Course, 1: Classroom} */
    private function courseAndClassroom(User $teacher, string $slug): array
    {
        $course = Course::query()->create([
            'title' => 'دوره ویدیویی',
            'slug' => $slug,
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'spotplayer',
            'status' => 'published',
        ]);

        $classroom = Classroom::query()->create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'name' => 'کلاس ویدیویی',
            'status' => 'active',
        ]);

        return [$course, $classroom];
    }

    public function test_course_enrollment_cannot_unlock_a_video_assigned_to_a_different_classroom_course(): void
    {
        $student = $this->userWithRole('student');
        $teacher = $this->userWithRole('teacher');
        [$courseA, $classA] = $this->courseAndClassroom($teacher, 'video-course-a');
        [$courseB, $classB] = $this->courseAndClassroom($teacher, 'video-course-b');

        $classA->students()->attach($student->id);
        $classB->students()->attach($student->id);
        $enrollments = app(CourseEnrollmentService::class);
        $enrollments->syncClassroomRoster($classA);
        $enrollments->syncClassroomRoster($classB);

        Storage::disk('local')->put('private/spotplayer-staging/wrong-class.mp4', 'private-video');
        $video = SpotPlayerVideo::query()->create([
            'course_id' => $courseA->id,
            'classroom_id' => $classB->id,
            'created_by' => $teacher->id,
            'title' => 'ویدیوی کلاس با دوره ناسازگار',
            'status' => 'waiting_contract',
            'file_path' => 'private/spotplayer-staging/wrong-class.mp4',
            'file_original_name' => 'wrong-class.mp4',
            'file_mime_type' => 'video/mp4',
            'file_size_bytes' => 13,
            'file_sha256' => hash('sha256', 'private-video'),
            'staged_at' => now(),
        ]);

        $this->actingAs($student)
            ->get(route('student.videos.show', $video))
            ->assertNotFound();
    }

}
