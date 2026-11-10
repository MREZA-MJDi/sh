<?php

namespace Tests\\Feature;

use App\\Models\\Role;
use App\\Models\\User;
use Database\\Seeders\\RolePermissionSeeder;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

final class ParentPortalDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }

    public function test_guardian_can_switch_between_linked_children(): void
    {
        $guardian = $this->userWithRole('parent');
        $firstChild = $this->userWithRole('student', ['name' => 'دانش‌آموز اول']);
        $secondChild = $this->userWithRole('student', ['name' => 'دانش‌آموز دوم']);
        $unlinkedChild = $this->userWithRole('student', ['name' => 'دانش‌آموز غیرمرتبط']);

        $guardian->children()->attach([$firstChild->id, $secondChild->id]);

        $this->actingAs($guardian)
            ->get(route('guardian.dashboard', ['child' => $secondChild->id]))
            ->assertOk()
            ->assertSee('وضعیت آموزشی '.$secondChild->name, false)
            ->assertSee('value="'.$secondChild->id.'" selected', false)
            ->assertDontSee($unlinkedChild->name);
    }

    public function test_invalid_child_selection_never_exposes_an_unlinked_student(): void
    {
        $guardian = $this->userWithRole('parent');
        $linkedChild = $this->userWithRole('student', ['name' => 'فرزند مجاز']);
        $unlinkedChild = $this->userWithRole('student', ['name' => 'فرزند غیرمجاز']);

        $guardian->children()->attach($linkedChild->id);

        $this->actingAs($guardian)
            ->get(route('guardian.dashboard', ['child' => $unlinkedChild->id]))
            ->assertOk()
            ->assertSee('وضعیت آموزشی '.$linkedChild->name, false)
            ->assertSee('value="'.$linkedChild->id.'" selected', false)
            ->assertDontSee($unlinkedChild->name);
    }
}
