<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Homework;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class HomeworkManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
        Storage::fake('local');
    }

    public function test_admin_can_update_and_delete_homework_and_replace_attachment(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());
        $classroom = Classroom::query()->create(['teacher_id' => $admin->id, 'name' => 'کلاس مدیریت', 'status' => 'active']);
        Storage::disk('local')->put('private/homework/old.pdf', 'old');
        $homework = Homework::query()->create([
            'classroom_id' => $classroom->id,
            'title' => 'تکلیف اولیه',
            'instructions' => 'متن اولیه',
            'attachment_path' => 'private/homework/old.pdf',
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->patch(route('admin.homework.update', $homework), [
            'classroom_id' => $classroom->id,
            'title' => 'تکلیف اصلاح‌شده',
            'instructions' => 'دستورالعمل اصلاح‌شده',
            'due_at' => now()->subDay()->format('Y-m-d H:i:s'),
            'status' => 'published',
            'attachment' => UploadedFile::fake()->create('new.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $updated = $homework->fresh();
        $this->assertSame('تکلیف اصلاح‌شده', $updated->title);
        $this->assertNotSame('private/homework/old.pdf', $updated->attachment_path);
        Storage::disk('local')->assertMissing('private/homework/old.pdf');
        Storage::disk('local')->assertExists($updated->attachment_path);

        $this->actingAs($admin)->delete(route('admin.homework.destroy', $updated))->assertRedirect();
        $this->assertDatabaseMissing('homeworks', ['id' => $homework->id]);
        Storage::disk('local')->assertMissing($updated->attachment_path);
    }
}
