<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\LearningResource;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\AcademicGradeSeeder;
use Database\Seeders\ClientAcceptanceSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class ClientAcceptanceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_acceptance_fixtures_are_scoped_editable_and_not_recreated(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicGradeSeeder::class);
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $variables = [
            'SHIKHAN_QA_SEED' => '1',
            'SHIKHAN_QA_PASSWORD' => 'Test-only-password-123!',
            'SHIKHAN_QA_ADMIN_EMAIL' => $admin->email,
        ];
        $previous = [];
        foreach ($variables as $key => $value) {
            $previous[$key] = getenv($key);
            putenv($key.'='.$value);
        }

        try {
            $this->seed(ClientAcceptanceSeeder::class);

            $this->assertSame(8, User::query()->where('email', 'like', '%@academy.example.test')->count());
            $this->assertSame(3, Course::query()->where('slug', 'like', 'demo-academy-%')->count());
            $student = User::query()->where('email', 'armin.mehri@academy.example.test')->firstOrFail();
            $other = User::query()->where('email', 'helia.karimi@academy.example.test')->firstOrFail();
            $empty = User::query()->where('email', 'parsa.rostami@academy.example.test')->firstOrFail();
            $resource = LearningResource::query()->where('title', 'جزوه مفاهیم پایه و حل مسئله')->firstOrFail();
            Storage::disk('local')->assertExists($resource->file_path);
            $this->assertStringStartsWith('%PDF-1.4', Storage::disk('local')->get($resource->file_path));
            $this->actingAs($student)->get(route('student.resources.download', $resource))->assertOk();
            $this->actingAs($other)->get(route('student.resources.download', $resource))->assertNotFound();
            $this->actingAs($empty)->get(route('student.resources.download', $resource))->assertNotFound();

            $course = Course::query()->where('slug', 'demo-academy-math-problem-solving')->firstOrFail();
            $course->update(['title' => 'Edited by administrator']);
            $draft = Course::query()->where('slug', 'demo-academy-study-skills')->firstOrFail();
            $this->actingAs($admin)->delete(route('admin.courses.destroy', $draft))->assertRedirect();
            $this->assertDatabaseMissing('courses', ['id' => $draft->id]);

            $this->seed(ClientAcceptanceSeeder::class);
            $this->assertSame('Edited by administrator', $course->fresh()->title);
            $this->assertDatabaseMissing('courses', ['slug' => 'demo-academy-study-skills']);
        } finally {
            foreach ($previous as $key => $value) {
                putenv($value === false ? $key : $key.'='.$value);
            }
        }
    }
}
