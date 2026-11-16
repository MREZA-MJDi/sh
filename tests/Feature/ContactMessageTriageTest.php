<?php

namespace Tests\\Feature;

use App\\Models\\ContactMessage;
use App\\Models\\Role;
use App\\Models\\User;
use Database\\Seeders\\RolePermissionSeeder;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

final class ContactMessageTriageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_admin_can_filter_and_update_contact_message_status_with_audit_actor(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));
        $message = ContactMessage::query()->create([
            'name' => 'فرستنده آزمایشی',
            'contact' => 'test@example.invalid',
            'topic' => 'پشتیبانی',
            'body' => 'متن پیام آزمایشی',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contact-messages.index', ['status' => 'new']))
            ->assertOk()
            ->assertSee('فرستنده آزمایشی');

        $this->actingAs($admin)
            ->patch(route('admin.contact-messages.status', $message), ['status' => 'in_progress'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'status' => 'in_progress',
            'handled_by' => $admin->id,
        ]);
        $this->assertNotNull($message->fresh()->handled_at);
    }

    public function test_contact_message_status_rejects_unknown_values(): void
    {
        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));
        $message = ContactMessage::query()->create([
            'name' => 'فرستنده آزمایشی',
            'contact' => 'test@example.invalid',
            'topic' => 'پشتیبانی',
            'body' => 'متن پیام آزمایشی',
        ]);

        $this->actingAs($admin)
            ->from(route('admin.contact-messages.index'))
            ->patch(route('admin.contact-messages.status', $message), ['status' => 'anything'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('contact_messages', [
            'id' => $message->id,
            'status' => 'new',
            'handled_by' => null,
        ]);
    }
}
