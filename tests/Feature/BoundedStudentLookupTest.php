<?php

namespace Tests\\Feature;

use App\\Models\\Role;
use App\\Models\\User;
use Database\\Seeders\\RolePermissionSeeder;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

final class BoundedStudentLookupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_student_lookup_requires_a_search_term_and_caps_results(): void
    {
        $admin = $this->userWithRole('admin');

        for ($index = 1; $index <= 25; $index++) {
            $this->userWithRole('student', ['name' => sprintf('دانش‌آموز تست %02d', $index)]);
        }

        $this->actingAs($admin)
            ->getJson(route('admin.lookups.students'))
            ->assertUnprocessable();

        $response = $this->actingAs($admin)
            ->getJson(route('admin.lookups.students', ['q' => 'دانش‌آموز']))
            ->assertOk()
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('has_more', true)
            ->assertJsonPath('limit', 20);

        $this->assertCount(20, $response->json('data'));
    }

    public function test_only_users_with_user_management_permission_can_search_students(): void
    {
        $parent = $this->userWithRole('parent');

        $this->actingAs($parent)
            ->getJson(route('admin.lookups.students', ['q' => 'علی']))
            ->assertForbidden();
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
