<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\SeoArticle;
use App\Models\SeoSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SeoSitemapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_sitemap_index_exposes_separate_static_and_content_sitemaps(): void
    {
        $settings = SeoSetting::current();
        $settings->forceFill(['canonical_base_url' => 'https://academy.example.test'])->save();

        Course::query()->create([
            'title' => 'دوره عمومی',
            'slug' => 'public-course',
            'type' => 'free',
            'price' => 0,
            'status' => 'published',
        ]);
        Course::query()->create([
            'title' => 'دوره پیش‌نویس',
            'slug' => 'draft-course',
            'type' => 'free',
            'price' => 0,
            'status' => 'draft',
        ]);

        $teacherRole = Role::query()->firstOrCreate(['name' => 'teacher'], ['label' => 'مدرس']);
        $teacher = User::factory()->create(['is_active' => true]);
        $teacher->roles()->attach($teacherRole);

        SeoArticle::query()->create([
            'title' => 'مقاله عمومی',
            'slug' => 'public-article',
            'body' => '<p>متن مقاله</p>',
            'status' => 'published',
            'published_at' => now(),
            'canonical_url' => 'javascript:alert(1)',
        ]);

        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertSee('<sitemapindex', false)
            ->assertSee('https://academy.example.test/sitemap/static.xml', false)
            ->assertSee('https://academy.example.test/sitemap/courses.xml?page=1', false)
            ->assertSee('https://academy.example.test/sitemap/teachers.xml?page=1', false)
            ->assertSee('https://academy.example.test/sitemap/articles.xml?page=1', false);

        $this->get(route('seo.sitemap.section', ['type' => 'courses']))
            ->assertOk()
            ->assertSee('https://academy.example.test/courses/public-course', false)
            ->assertDontSee('draft-course');

        $this->get(route('seo.sitemap.section', ['type' => 'teachers']))
            ->assertOk()
            ->assertSee('https://academy.example.test/teachers/'.$teacher->id, false);

        $this->get(route('seo.sitemap.section', ['type' => 'articles']))
            ->assertOk()
            ->assertSee('https://academy.example.test/articles/public-article', false)
            ->assertDontSee('javascript:alert');
    }

    public function test_sitemap_respects_indexing_and_enabled_settings(): void
    {
        $settings = SeoSetting::current();
        $settings->forceFill(['robots_index' => false])->save();

        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertSee('<urlset', false)
            ->assertDontSee('<sitemapindex', false);

        $settings->forceFill(['sitemap_enabled' => false])->save();

        $this->get(route('seo.sitemap'))->assertNotFound();
        $this->get(route('seo.sitemap.section', ['type' => 'static']))->assertNotFound();
    }

    public function test_sitemap_sections_are_limited_to_the_standard_url_cap(): void
    {
        $reflection = new \ReflectionClass(\App\Http\Controllers\SeoPublicController::class);
        $this->assertSame(50000, $reflection->getConstant('SITEMAP_URL_LIMIT'));
    }
}
