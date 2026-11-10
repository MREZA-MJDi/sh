<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PrivateAreaIndexingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_authentication_pages_are_not_indexed(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow, noarchive"', false);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow, noarchive"', false);
    }

    public function test_admin_teacher_student_and_parent_workspaces_are_not_indexed(): void
    {
        foreach ([
            ['admin', 'admin.dashboard'],
            ['teacher', 'teacher.dashboard'],
            ['student', 'student.dashboard'],
            ['parent', 'guardian.dashboard'],
        ] as [$role, $route]) {
            $user = User::factory()->create();
            $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertSee('name="robots" content="noindex, nofollow, noarchive"', false);
        }
    }
}
