<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class TeacherProfileWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_teacher_can_view_profile_page_and_update_information(): void
    {
        $teacher = User::factory()->create([
            'email' => 'teacher-profile@example.invalid',
            'subject' => 'شیمی',
            'profile_note' => 'دبیر باسابقه شیمی',
        ]);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $this->actingAs($teacher)
            ->get(route('teacher.profile.edit'))
            ->assertOk()
            ->assertSee('پروفایل و تصویر مدرس')
            ->assertSee('teacher-profile@example.invalid')
            ->assertSee('دبیر باسابقه شیمی');

        $this->actingAs($teacher)
            ->put(route('teacher.profile.update'), [
                'name' => 'استاد نام جدید',
                'phone' => '۰۹۱۲۹۸۷۶۵۴۳',
                'subject' => 'شیمی کنکور',
                'profile_note' => 'مدرس رتبه‌های برتر',
            ])
            ->assertRedirect(route('teacher.profile.edit'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $teacher->id,
            'name' => 'استاد نام جدید',
            'phone' => '09129876543',
            'subject' => 'شیمی کنکور',
            'profile_note' => 'مدرس رتبه‌های برتر',
        ]);
    }

    public function test_teacher_can_upload_and_remove_avatar(): void
    {
        Storage::fake('public');

        $teacher = User::factory()->create([
            'email' => 'teacher-avatar@example.invalid',
            'subject' => 'فیزیک',
        ]);
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $avatarFile = UploadedFile::fake()->create('teacher-photo.png', 200, 'image/png');

        $this->actingAs($teacher)
            ->put(route('teacher.profile.update'), [
                'name' => $teacher->name,
                'avatar' => $avatarFile,
            ])
            ->assertRedirect(route('teacher.profile.edit'))
            ->assertSessionHas('success');

        $teacher->refresh();
        $this->assertNotNull($teacher->avatar_path);
        Storage::disk('public')->assertExists($teacher->avatar_path);

        // Verify avatar appears in teacher profile and sidebar
        $this->actingAs($teacher)
            ->get(route('teacher.profile.edit'))
            ->assertOk()
            ->assertSee($teacher->avatarUrl(), false);

        $this->actingAs($teacher)
            ->get(route('teacher.dashboard'))
            ->assertOk()
            ->assertSee($teacher->avatarUrl(), false);

        // Verify public pages show avatar
        $this->get(route('public.teachers.index'))
            ->assertOk()
            ->assertSee($teacher->avatarUrl(), false);

        $this->get(route('public.teachers.show', $teacher))
            ->assertOk()
            ->assertSee($teacher->avatarUrl(), false);

        // Remove avatar
        $this->actingAs($teacher)
            ->put(route('teacher.profile.update'), [
                'name' => $teacher->name,
                'remove_avatar' => 1,
            ])
            ->assertRedirect(route('teacher.profile.edit'));

        $teacher->refresh();
        $this->assertNull($teacher->avatar_path);
    }
}
