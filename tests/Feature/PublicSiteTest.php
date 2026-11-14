<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\SeoArticle;
use App\Models\SeoBanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_homepage_displays_only_published_courses_articles_and_active_home_banners(): void
    {
        Storage::disk('public')->put('seo/banners/home.jpg', 'image');

        SeoBanner::create([
            'title' => 'بنر صفحه اصلی',
            'alt_text' => 'بنر آموزشی',
            'image_path' => 'seo/banners/home.jpg',
            'placement' => 'home_hero',
            'sort_order' => 1,
            'status' => 'active',
        ]);

        SeoBanner::create([
            'title' => 'بنر غیرفعال',
            'alt_text' => 'بنر غیرفعال',
            'image_path' => 'seo/banners/home.jpg',
            'placement' => 'home_hero',
            'status' => 'inactive',
        ]);

        $published = Course::create([
            'title' => 'دوره منتشرشده',
            'slug' => 'published-course',
            'description' => 'توضیح دوره',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        Course::create([
            'title' => 'دوره پیش‌نویس',
            'slug' => 'draft-course',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'draft',
        ]);

        $section = $published->sections()->create([
            'title' => 'بخش منتشرشده',
            'status' => 'published',
        ]);

        $section->lessons()->create([
            'title' => 'درس منتشرشده',
            'status' => 'published',
        ]);

        SeoArticle::create([
            'title' => 'مقاله منتشرشده',
            'slug' => 'published-article',
            'body' => '<p>محتوای مقاله</p>',
            'status' => 'published',
            'published_at' => now()->subMinute(),
        ]);

        SeoArticle::create([
            'title' => 'مقاله پیش‌نویس',
            'slug' => 'draft-article',
            'body' => '<p>پیش‌نویس</p>',
            'status' => 'draft',
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('بنر صفحه اصلی')
            ->assertDontSee('بنر غیرفعال')
            ->assertSee('دوره منتشرشده')
            ->assertDontSee('دوره پیش‌نویس')
            ->assertSee('مقاله منتشرشده')
            ->assertDontSee('مقاله پیش‌نویس');
    }

    public function test_public_course_page_exposes_only_published_sections_and_lessons(): void
    {
        $course = Course::create([
            'title' => 'دوره عمومی',
            'slug' => 'public-course',
            'description' => 'معرفی دوره',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $publishedSection = $course->sections()->create([
            'title' => 'سرفصل قابل مشاهده',
            'status' => 'published',
        ]);

        $publishedSection->lessons()->create([
            'title' => 'درس قابل مشاهده',
            'status' => 'published',
        ]);

        $publishedSection->lessons()->create([
            'title' => 'درس پیش‌نویس',
            'status' => 'draft',
        ]);

        $course->sections()->create([
            'title' => 'سرفصل پیش‌نویس',
            'status' => 'draft',
        ]);

        $this->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('دوره عمومی')
            ->assertSee('سرفصل قابل مشاهده')
            ->assertSee('درس قابل مشاهده')
            ->assertDontSee('درس پیش‌نویس')
            ->assertDontSee('سرفصل پیش‌نویس');
    }


    public function test_public_course_listing_uses_published_courses_only(): void
    {
        Course::create([
            'title' => 'دوره قابل مشاهده',
            'slug' => 'visible-course',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        Course::create([
            'title' => 'دوره غیرقابل مشاهده',
            'slug' => 'not-visible-course',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'draft',
        ]);

        $this->get(route('public.courses.index'))
            ->assertOk()
            ->assertSee('دوره قابل مشاهده')
            ->assertDontSee('دوره غیرقابل مشاهده')
            ->assertSee(route('public.courses.show', 'visible-course'));
    }

    public function test_unpublished_course_is_not_publicly_resolvable(): void
    {
        $course = Course::create([
            'title' => 'دوره مخفی',
            'slug' => 'hidden-course',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'draft',
        ]);

        $this->get(route('public.courses.show', $course->slug))->assertNotFound();
    }

    public function test_public_teacher_directory_only_lists_active_teacher_accounts(): void
    {
        $role = Role::firstOrCreate(['name' => 'teacher'], ['label' => 'مدرس']);

        $teacher = User::create([
            'name' => 'مدرس فعال',
            'email' => 'teacher-public@example.test',
            'password' => 'password-password',
            'is_active' => true,
            'subject' => 'ریاضی',
        ]);
        $teacher->roles()->attach($role);

        $inactiveTeacher = User::create([
            'name' => 'مدرس غیرفعال',
            'email' => 'teacher-inactive@example.test',
            'password' => 'password-password',
            'is_active' => false,
        ]);
        $inactiveTeacher->roles()->attach($role);

        $this->get(route('public.teachers.index'))
            ->assertOk()
            ->assertSee('مدرس فعال')
            ->assertDontSee('مدرس غیرفعال');
    }

    public function test_public_contact_form_persists_a_message_for_the_admin_inbox(): void
    {
        $this->post(route('public.contact.store'), [
            'name' => 'کاربر آزمایشی',
            'contact' => 'user@example.test',
            'topic' => 'پشتیبانی دوره',
            'body' => 'برای انتخاب دوره راهنمایی می‌خواهم.',
        ])->assertRedirect();

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'کاربر آزمایشی',
            'contact' => 'user@example.test',
            'topic' => 'پشتیبانی دوره',
            'body' => 'برای انتخاب دوره راهنمایی می‌خواهم.',
        ]);
    }

    public function test_public_cart_keeps_only_published_courses_in_the_session(): void
    {
        $published = Course::create([
            'title' => 'دوره سبد خرید',
            'slug' => 'cart-course',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $draft = Course::create([
            'title' => 'دوره پیش‌نویس سبد',
            'slug' => 'draft-cart-course',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'draft',
        ]);

        $this->post(route('public.cart.add', $published))->assertRedirect(route('public.cart'));
        $this->get(route('public.cart'))->assertOk()->assertSee('دوره سبد خرید')->assertDontSee('دوره پیش‌نویس سبد');

        $this->post(route('public.cart.add', $draft))->assertNotFound();
    }

    public function test_public_course_filters_apply_only_to_published_courses(): void
    {
        Course::create([
            'title' => 'ریاضی منتشرشده',
            'slug' => 'published-math-filter',
            'subject' => 'ریاضی',
            'grade' => '12',
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        Course::create([
            'title' => 'فیزیک منتشرشده',
            'slug' => 'published-physics-filter',
            'subject' => 'فیزیک',
            'grade' => '12',
            'type' => 'paid',
            'price' => 1000,
            'delivery_type' => 'onsite',
            'status' => 'published',
        ]);

        $this->get(route('public.courses.index', ['subject' => 'ریاضی']))
            ->assertOk()
            ->assertSee('ریاضی منتشرشده')
            ->assertDontSee('فیزیک منتشرشده');
    }

    public function test_public_teacher_profile_displays_teacher_info_and_published_courses(): void
    {
        $role = Role::firstOrCreate(['name' => 'teacher'], ['label' => 'مدرس']);

        $teacher = User::create([
            'name' => 'استاد محمدی',
            'email' => 'mohammadi@example.test',
            'password' => 'password-password',
            'is_active' => true,
            'subject' => 'ریاضیات کنکور',
            'profile_note' => 'مدرس باسابقه کنکور با ۱۰ سال سابقه تدریس تخصصی.',
        ]);
        $teacher->roles()->attach($role);

        $publishedCourse = Course::create([
            'title' => 'حسابان پیشرفته',
            'slug' => 'hesaban-pishrafte',
            'subject' => 'ریاضیات کنکور',
            'teacher_id' => $teacher->id,
            'type' => 'paid',
            'price' => 500000,
            'delivery_type' => 'online',
            'status' => 'published',
        ]);

        $section = $publishedCourse->sections()->create([
            'title' => 'فصل اول',
            'status' => 'published',
        ]);
        $section->lessons()->create([
            'title' => 'درس اول',
            'status' => 'published',
        ]);

        $draftCourse = Course::create([
            'title' => 'هندسه پیش‌نویس',
            'slug' => 'hendese-draft',
            'subject' => 'ریاضیات کنکور',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'online',
            'status' => 'draft',
        ]);

        $response = $this->get(route('public.teachers.show', $teacher));

        $response->assertOk()
            ->assertSee('استاد محمدی')
            ->assertSee('ریاضیات کنکور')
            ->assertSee('مدرس باسابقه کنکور با ۱۰ سال سابقه تدریس تخصصی.')
            ->assertSee('حسابان پیشرفته')
            ->assertDontSee('هندسه پیش‌نویس');
    }

    public function test_public_teacher_profile_returns_404_for_inactive_teacher_or_non_teacher(): void
    {
        $teacherRole = Role::firstOrCreate(['name' => 'teacher'], ['label' => 'مدرس']);
        $studentRole = Role::firstOrCreate(['name' => 'student'], ['label' => 'دانش‌آموز']);

        $inactiveTeacher = User::create([
            'name' => 'مدرس غیرفعال',
            'email' => 'inactive-prof@example.test',
            'password' => 'password-password',
            'is_active' => false,
            'subject' => 'فیزیک',
        ]);
        $inactiveTeacher->roles()->attach($teacherRole);

        $student = User::create([
            'name' => 'دانش‌آموز ساده',
            'email' => 'student-prof@example.test',
            'password' => 'password-password',
            'is_active' => true,
        ]);
        $student->roles()->attach($studentRole);

        $this->get(route('public.teachers.show', $inactiveTeacher))->assertNotFound();
        $this->get(route('public.teachers.show', $student))->assertNotFound();
    }

    public function test_course_detail_links_to_teacher_public_profile(): void
    {
        $role = Role::firstOrCreate(['name' => 'teacher'], ['label' => 'مدرس']);

        $teacher = User::create([
            'name' => 'دکتر سمیعی',
            'email' => 'samiei@example.test',
            'password' => 'password-password',
            'is_active' => true,
            'subject' => 'زیست‌شناسی',
            'profile_note' => 'دکترای ژنتیک و مدرس دوره‌های زیست.',
        ]);
        $teacher->roles()->attach($role);

        $course = Course::create([
            'title' => 'زیست جامع کنکور',
            'slug' => 'zist-jame',
            'subject' => 'زیست‌شناسی',
            'teacher_id' => $teacher->id,
            'type' => 'free',
            'price' => 0,
            'delivery_type' => 'online',
            'status' => 'published',
        ]);

        $this->get(route('public.courses.show', $course->slug))
            ->assertOk()
            ->assertSee('دکتر سمیعی')
            ->assertSee(route('public.teachers.show', $teacher))
            ->assertSee('دکترای ژنتیک و مدرس دوره‌های زیست.');
    }

}
