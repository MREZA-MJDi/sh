<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AdminInboxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_admin_can_open_a_real_conversation_in_the_three_column_inbox(): void
    {
        $admin = $this->userWithRole('admin');
        $parent = $this->userWithRole('parent');
        $message = $this->conversation($parent, $admin);

        $this->actingAs($admin)
            ->get(route('admin.messages.index', ['message' => $message->id]))
            ->assertOk()
            ->assertSee('کارتابل پیام‌ها')
            ->assertSee('گفتگوها')
            ->assertSee('یادداشت‌های داخلی')
            ->assertSee($parent->name)
            ->assertViewHas('partner', fn ($partner): bool => $partner->is($parent));
    }

    public function test_admin_can_close_reopen_and_add_a_private_note_to_a_conversation(): void
    {
        $admin = $this->userWithRole('admin');
        $parent = $this->userWithRole('parent');
        $message = $this->conversation($parent, $admin);

        $this->actingAs($admin)
            ->from(route('admin.messages.index', ['message' => $message->id]))
            ->post(route('admin.messages.close', $message))
            ->assertRedirect();

        $this->assertNotNull($message->fresh()->closed_at);

        $this->post(route('admin.messages.reopen', $message))->assertRedirect();
        $this->assertNull($message->fresh()->closed_at);

        $this->post(route('admin.messages.notes.store', $message), [
            'body' => 'پیگیری تماس با ولی',
        ])->assertRedirect();

        $this->assertDatabaseHas('message_internal_notes', [
            'message_id' => $message->id,
            'user_id' => $parent->id,
            'created_by' => $admin->id,
            'body' => 'پیگیری تماس با ولی',
        ]);
    }

    public function test_admin_can_update_only_the_non_privileged_conversation_participant(): void
    {
        $admin = $this->userWithRole('admin');
        $parent = $this->userWithRole('parent');
        $message = $this->conversation($parent, $admin);

        $this->actingAs($admin)
            ->post(route('admin.messages.participant.update', $message), [
                'name' => 'ولی ویرایش‌شده',
                'email' => $parent->email,
                'phone' => null,
                'password' => 'A-new-strong-password-2026',
            ])
            ->assertRedirect();

        $parent->refresh();
        $this->assertSame('ولی ویرایش‌شده', $parent->name);
        $this->assertTrue(Hash::check('A-new-strong-password-2026', $parent->password));
    }

    public function test_admin_cannot_edit_another_privileged_account_through_a_conversation(): void
    {
        $admin = $this->userWithRole('admin');
        $anotherAdmin = $this->userWithRole('admin');
        $message = $this->conversation($anotherAdmin, $admin);

        $this->actingAs($admin)
            ->post(route('admin.messages.participant.update', $message), [
                'name' => 'تغییر غیرمجاز',
                'email' => $anotherAdmin->email,
                'phone' => null,
            ])
            ->assertForbidden();

        $this->assertNotSame('تغییر غیرمجاز', $anotherAdmin->fresh()->name);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', $role)->value('id'));

        return $user;
    }

    private function conversation(User $sender, User $recipient): Message
    {
        return Message::query()->create([
            'sender_id' => $sender->id,
            'recipient_id' => $recipient->id,
            'subject' => 'پیگیری وضعیت آموزشی',
            'body' => 'لطفاً درباره وضعیت دانش‌آموز اطلاع دهید.',
            'status' => 'open',
        ]);
    }
}
