<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\SeoTestimonial;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoTestimonialManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    public function test_seo_role_can_manage_testimonials_and_only_published_items_appear_on_homepage(): void
    {
        $seo = User::factory()->create();
        $seo->roles()->attach(Role::query()->where('name', 'seo')->firstOrFail());

        $this->actingAs($seo)->get(route('admin.seo.testimonials.index'))->assertOk();

        $this->actingAs($seo)->post(route('admin.seo.testimonials.store'), [
            'name' => 'دانش‌آموز آزمایشی',
            'role' => 'پایه دوازدهم',
            'quote' => 'این یک نظر آزمایشی است که پس از تأیید منتشر می‌شود.',
            'rating' => 5,
            'status' => 'draft',
            'sort_order' => 10,
        ])->assertRedirect();

        $testimonial = SeoTestimonial::query()->where('name', 'دانش‌آموز آزمایشی')->firstOrFail();
        $this->assertSame('draft', $testimonial->status);
        $this->get('/')->assertDontSee('دانش‌آموز آزمایشی');

        $this->actingAs($seo)->patch(route('admin.seo.testimonials.update', $testimonial), [
            'name' => 'دانش‌آموز تأییدشده',
            'role' => 'پایه دوازدهم',
            'quote' => 'نظر تأییدشده از پنل سئو.',
            'rating' => 4,
            'status' => 'published',
            'sort_order' => 1,
        ])->assertRedirect();

        $this->get('/')->assertSee('دانش‌آموز تأییدشده')->assertSee('نظر تأییدشده از پنل سئو.');

        $this->actingAs($seo)->delete(route('admin.seo.testimonials.destroy', $testimonial))->assertRedirect();
        $this->assertDatabaseMissing('seo_testimonials', ['id' => $testimonial->id]);
    }
}
