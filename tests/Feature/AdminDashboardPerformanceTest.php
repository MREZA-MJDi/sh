<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class AdminDashboardPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_dashboard_stays_within_a_bounded_query_budget_and_uses_a_date_range_for_attendance(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->firstOrFail());

        $queries = [];
        $databaseTimeMs = 0.0;
        DB::listen(function (QueryExecuted $query) use (&$queries, &$databaseTimeMs): void {
            $queries[] = strtolower($query->sql);
            $databaseTimeMs += $query->time;
        });

        $this->withoutVite()
            ->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();

        fwrite(STDERR, sprintf(
            "Admin dashboard SQL profile: %d queries, %.2f ms cumulative database time.\\n",
            count($queries),
            $databaseTimeMs
        ));

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
