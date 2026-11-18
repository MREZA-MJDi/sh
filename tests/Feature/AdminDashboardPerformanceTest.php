<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminDashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;\n\n    protected function setUp(): void\n    {\n        parent::setUp();\n        $this->seed(RolePermissionSeeder::class);\n    }

    public function test_admin_dashboard_stays_within_a_bounded_query_budget_and_uses_a_date_range_for_attendance(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();

        $this->assertLessThanOrEqual(
            35,
            count($queries),
            'The admin dashboard should not issue an unbounded number of SQL queries.'
        );

        $this->assertTrue(
            collect($queries)->contains(
                fn (string $sql): bool => str_contains($sql, 'attendances')
                    && str_contains($sql, 'between')
            ),
            'Weekly attendance aggregation should use an index-friendly date range.'
        );
    }
}
