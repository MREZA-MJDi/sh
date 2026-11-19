<?php

namespace Tests\\Feature;

use App\\Models\\Message;
use App\\Models\\Role;
use App\\Models\\User;
use Database\\Seeders\\RolePermissionSeeder;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

final class AdminCommunicationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_communication_center_paginates_all_threads_and_marks_incoming_and_outgoing_messages(): void
    {
        $admin = $this->userWithRole('admin');
        $parent = $this->userWithRole('parent');

        for ($index = 1; $index <= 35; $index++) {
            Message::query()->create([
                'sender_id' => $parent->id,
                'recipient_id' => $admin->id,
                'subject' => 'پیام دریافتی '.$index,
                'body' => 'متن پیام '.$index,
                'status' => 'open',
            ]);
        }

        Message::query()->create([
            'sender_id' => $admin->id,
            'recipient_id' => $parent->id,
            'subject' => 'پیام ارسالی مدیر',
            'body' => 'پاسخ تیم پشتیبانی',
            'status' => 'answered',
        ]);

        $firstPage = $this->actingAs($admin)->get(route('admin.communication-center.index'));

        $firstPage->assertOk()
            ->assertSee('پیام ارسالی مدیر')
            ->assertSee('دریافتی')
            ->assertSee('ارسالی')
            ->assertSee('data-direction="incoming"', false)
            ->assertSee('data-direction="outgoing"', false)
            ->assertViewHas('items', fn ($items): bool => $items->total() === 36 && $items->count() === 30)
            ->assertViewHas('counts', fn (array $counts): bool => $counts['messages'] === 36);

        $secondPage = $this->get(route('admin.communication-center.index', ['page' => 2]));

        $secondPage->assertOk()
            ->assertViewHas('items', fn ($items): bool => $items->total() === 36 && $items->count() === 6 && ! $items->hasMorePages());
    }

    public function test_filtered_communication_center_uses_the_filtered_total_for_pagination(): void
    {
        $admin = $this->userWithRole('admin');
        $parent = $this->userWithRole('parent');

        for ($index = 1; $index <= 32; $index++) {
            Message::query()->create([
                'sender_id' => $parent->id,
                'recipient_id' => $admin->id,
                'subject' => 'پیام '.$index,
                'body' => 'متن پیام',
                'status' => 'open',
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.communication-center.index', ['type' => 'messages', 'page' => 2]))
            ->assertOk()
            ->assertViewHas('items', fn ($items): bool => $items->total() === 32 && $items->count() === 2);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }
}
