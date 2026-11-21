<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DemoLoginCardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_explicit_demo_login_cards_show_all_roles_and_shared_password_in_testing(): void
    {
        config([
            'demo.enabled' => true,
            'demo.login_cards_enabled' => true,
            'demo.password' => 'Shikhan-Demo-2026!',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('admin@sh.local')
            ->assertSee('seo@sh.local')
            ->assertSee('teacher@sh.local')
            ->assertSee('student@sh.local')
            ->assertSee('parent@sh.local')
            ->assertSee('Shikhan-Demo-2026!');
    }

    public function test_demo_login_cards_are_hidden_when_the_explicit_display_flag_is_off(): void
    {
        config([
            'demo.enabled' => true,
            'demo.login_cards_enabled' => false,
            'demo.password' => 'Shikhan-Demo-2026!',
            'demo.login_cards_key' => str_repeat('k', 48),
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('admin@sh.local')
            ->assertDontSee('Shikhan-Demo-2026!')
            ->assertDontSee('login.demo.unlock');
    }

    public function test_production_demo_cards_require_a_private_unlock_and_then_show_all_roles(): void
    {
        $accessKey = str_repeat('K9', 24);

        config([
            'demo.enabled' => true,
            'demo.login_cards_enabled' => true,
            'demo.password' => 'Shikhan-Demo-2026!',
            'demo.login_cards_key' => $accessKey,
        ]);
        $this->app['env'] = 'production';

        try {
            $this->get(route('login'))
                ->assertOk()
                ->assertSee(route('login.demo.unlock'))
                ->assertDontSee('admin@sh.local')
                ->assertDontSee('seo@sh.local')
                ->assertDontSee('Shikhan-Demo-2026!');

            $this->post(route('login.demo.unlock'), ['access_key' => $accessKey])
                ->assertRedirect(route('login'));

            $this->get(route('login'))
                ->assertOk()
                ->assertSee('admin@sh.local')
                ->assertSee('seo@sh.local')
                ->assertSee('teacher@sh.local')
                ->assertSee('student@sh.local')
                ->assertSee('parent@sh.local')
                ->assertSee('Shikhan-Demo-2026!');
        } finally {
            $this->app['env'] = 'testing';
        }
    }

    public function test_invalid_production_demo_unlock_key_does_not_reveal_credentials(): void
    {
        config([
            'demo.enabled' => true,
            'demo.login_cards_enabled' => true,
            'demo.password' => 'Shikhan-Demo-2026!',
            'demo.login_cards_key' => str_repeat('s', 48),
        ]);
        $this->app['env'] = 'production';

        try {
            $this->from(route('login'))
                ->post(route('login.demo.unlock'), ['access_key' => str_repeat('x', 48)])
                ->assertRedirect(route('login'))
                ->assertSessionHasErrors('access_key');

            $this->get(route('login'))
                ->assertOk()
                ->assertDontSee('admin@sh.local')
                ->assertDontSee('Shikhan-Demo-2026!');
        } finally {
            $this->app['env'] = 'testing';
        }
    }
}
