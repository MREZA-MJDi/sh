<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicIntegrationSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_public_discovery_pages_render_without_external_provider_credentials(): void
    {
        $this->get(route('home'))->assertOk()->assertViewIs('public.home.index');
        $this->get(route('public.courses.index'))->assertOk()->assertViewIs('public.courses.index');
        $this->get(route('public.teachers.index'))->assertOk()->assertViewIs('public.teachers.index');
        $this->get(route('public.contact'))->assertOk()->assertViewIs('public.contact');
        $this->get(route('public.cart'))->assertOk()->assertViewIs('public.cart');
        $this->get(route('seo.articles.index'))->assertOk()->assertViewIs('seo.public.index');
        $this->get(route('seo.robots'))->assertOk()->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function test_all_public_pages_render_without_lazy_loading_relationships(): void
    {
        $teacherRole = \\App\\Models\\Role::firstOrCreate(['name' => 'teacher'], ['label' => 'مدرس']);
        $teacher = \\App\\Models\\User::factory()->create([
            'name' => 'مدرس آزمون رندر',
            'is_active' => true,
            'subject' => 'زیست',
        ]);
        $teacher->roles()->attach($teacherRole);

        $course = Course::query()->create([
            'title' => 'دوره آزمون رندر عمومی',
            'slug' => 'public-render-regression-course',
            'teacher_id' => $teacher->id,
            'subject' => 'زیست',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);
        $section = $course->sections()->create([
            'title' => 'فصل آزمون رندر',
            'status' => 'published',
        ]);
        $section->lessons()->create([
            'title' => 'درس آزمون رندر',
            'status' => 'published',
        ]);

        \\App\\Models\\SeoArticle::query()->create([
            'author_id' => $teacher->id,
            'title' => 'مقاله آزمون رندر',
            'slug' => 'public-render-regression-article',
            'excerpt' => 'خلاصه مقاله آزمون رندر',
            'body' => '<h2>بخش مقاله</h2><p>متن مقاله برای تست رندر صفحات عمومی.</p>',
            'keywords' => ['آموزش'],
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        \\Illuminate\\Database\\Eloquent\\Model::preventLazyLoading();

        try {
            $this->get(route('home'))->assertOk()->assertSee('مدرس آزمون رندر')->assertSee('مقاله آزمون رندر');
            $this->get(route('public.courses.index'))->assertOk()->assertSee('دوره آزمون رندر عمومی');
            $this->get(route('public.courses.show', $course->slug))->assertOk()->assertSee('درس آزمون رندر');
            $this->get(route('public.teachers.index'))->assertOk()->assertSee('مدرس آزمون رندر');
            $this->get(route('public.teachers.show', $teacher))->assertOk()->assertSee('دوره آزمون رندر عمومی');
            $this->get(route('seo.articles.index'))->assertOk()->assertSee('مقاله آزمون رندر');
            $this->get(route('seo.articles.show', 'public-render-regression-article'))->assertOk()->assertSee('مقاله آزمون رندر');
            $this->get(route('public.contact'))->assertOk();
            $this->get(route('public.cart'))->assertOk();
            $this->get(route('seo.robots'))->assertOk();
            $this->get(route('seo.sitemap'))->assertOk();
        } finally {
            \\Illuminate\\Database\\Eloquent\\Model::preventLazyLoading(false);
        }
    }

    public function test_public_contact_form_persists_a_new_message_for_admin_triage(): void
    {
        $this->get(route('public.contact'))
            ->assertOk()
            ->assertSee(route('public.contact.store'), false);

        $this->from(route('public.contact'))
            ->post(route('public.contact.store'), [
                'name' => 'دانش‌آموز آزمایشی',
                'contact' => 'student@example.test',
                'topic' => 'پشتیبانی دوره',
                'body' => 'برای انتخاب دوره به راهنمایی نیاز دارم.',
            ])
            ->assertRedirect(route('public.contact'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'دانش‌آموز آزمایشی',
            'contact' => 'student@example.test',
            'topic' => 'پشتیبانی دوره',
            'status' => 'new',
        ]);
        $this->assertSame(1, ContactMessage::query()->count());
    }

    public function test_paid_course_cart_keeps_order_intent_pending_until_payment_provider_is_enabled(): void
    {
        $course = Course::query()->create([
            'title' => 'دوره پرداخت آزمایشی',
            'slug' => 'pending-payment-course',
            'type' => 'paid',
            'price' => 250000,
            'delivery_type' => 'online',
            'status' => 'published',
        ]);

        $this->post(route('public.cart.add', $course))
            ->assertRedirect(route('public.cart'))
            ->assertSessionHas('public_cart', [$course->id]);

        $this->get(route('public.cart'))
            ->assertOk()
            ->assertSee('دوره پرداخت آزمایشی')
            ->assertSee('پرداخت آنلاین در حال آماده‌سازی است')
            ->assertSee('در انتظار فعال‌سازی ارائه‌دهنده پرداخت')
            ->assertDontSee('پرداخت موفق');

        $this->delete(route('public.cart.remove', $course))
            ->assertRedirect(route('public.cart'))
            ->assertSessionHas('public_cart', []);

        $this->get(route('public.cart'))
            ->assertOk()
            ->assertSee('سبد خریدت خالی است.');
    }
}
