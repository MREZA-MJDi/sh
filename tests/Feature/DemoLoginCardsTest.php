<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DemoLoginCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_demo_login_cards_show_only_non_seo_roles_and_shared_password(): void
    {
        config([
            'demo.enabled' => true,
            'demo.login_cards_enabled' => true,
            'demo.password' => 'Shikhan-Demo-2026!',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('admin@sh.local')
            ->assertSee('teacher@sh.local')
            ->assertSee('student@sh.local')
            ->assertSee('parent@sh.local')
            ->assertSee('Shikhan-Demo-2026!')
            ->assertDontSee('seo@sh.local')
            ->assertDontSee('پنل سئو');
    }

    public function test_demo_login_cards_are_hidden_when_the_explicit_display_flag_is_off(): void
    {
        config([
            'demo.enabled' => true,
            'demo.login_cards_enabled' => false,
            'demo.password' => 'Shikhan-Demo-2026!',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('admin@sh.local')
            ->assertDontSee('Shikhan-Demo-2026!');
    }
}
