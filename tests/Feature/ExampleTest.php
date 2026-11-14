<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_is_public(): void
    {
        $this->withoutVite()
            ->get('/')
            ->assertOk()
            ->assertSee('مسیر یادگیری هوشمند');
    }
}
