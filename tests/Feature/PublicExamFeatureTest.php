<?php

namespace Tests\\Feature;

use App\\Models\\PublicExam;
use App\\Models\\PublicExamAttempt;
use App\\Models\\Role;
use App\\Models\\User;
use Database\\Seeders\\RolePermissionSeeder;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class PublicExamFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_exam_entry_is_disabled_by_default(): void
    {
        config(['public_exams.enabled' => false]);

        $this->withoutVite()
            ->get('/free-exams')
            ->assertNotFound();
    }

    public function test_only_published_exams_are_listed_when_the_feature_is_enabled(): void
    {
        config(['public_exams.enabled' => true]);

        $this->createExam(['title' => 'منتشرشده', 'slug' => 'published-test']);
        $this->createExam(['title' => 'پیش‌نویس', 'slug' => 'draft-test', 'is_published' => false]);

        $this->withoutVite()
            ->get('/free-exams')
            ->assertOk()
            ->assertSee('منتشرشده')
            ->assertDontSee('پیش‌نویس');
    }

    public function test_attempt_answers_are_validated_and_graded_on_the_server(): void
    {
        config(['public_exams.enabled' => true]);
        $exam = $this->createExam();
        $question = $exam->questions()->first();
        $correctOption = $question->options()->where('is_correct', true)->first();
        $wrongOption = $question->options()->where('is_correct', false)->first();

        $this->withoutVite()
            ->post(route('public.exams.start', $exam), [
                'name' => 'کاربر آزمایشی',
                'phone' => '۰۹۱۲۳۴۵۶۷۸۹',
            ])
            ->assertRedirect();

        $attempt = PublicExamAttempt::query()->sole();
        $this->assertSame('09123456789', $attempt->phone);

        $this->get(route('public.exams.take', $attempt))
            ->assertOk()
            ->assertDontSee('"is_correct"');

        $this->patchJson(route('public.exams.save', $attempt), [
            'question_id' => $question->id,
            'option_id' => 999999,
        ])->assertUnprocessable();

        $this->post(route('public.exams.submit', $attempt), [
            'answers' => [$question->id => $wrongOption->id],
        ])->assertRedirect(route('public.exams.result', $attempt));

        $attempt->refresh();
        $this->assertSame(0, $attempt->correct_count);
        $this->assertSame(1, $attempt->wrong_count);
        $this->assertSame(0, $attempt->blank_count);
        $this->assertSame(0.0, (float) $attempt->score_percent);

        $this->get(route('public.exams.result', $attempt))->assertOk();
    }

    public function test_existing_attempt_can_finish_after_public_access_is_disabled(): void
    {
        config(['public_exams.enabled' => true]);
        $exam = $this->createExam();
        $question = $exam->questions()->first();
        $correctOption = $question->options()->where('is_correct', true)->first();

        $this->post(route('public.exams.start', $exam), [
            'name' => 'کاربر آزمایشی',
            'phone' => '09123456789',
        ])->assertRedirect();

        $attempt = PublicExamAttempt::query()->sole();
        config(['public_exams.enabled' => false]);

        $this->post(route('public.exams.submit', $attempt), [
            'answers' => [$question->id => $correctOption->id],
        ])->assertRedirect(route('public.exams.result', $attempt));

        $attempt->refresh();
        $this->assertNotNull($attempt->submitted_at);
        $this->assertSame(100.0, (float) $attempt->score_percent);
        $this->get(route('public.exams.result', $attempt))->assertOk();
        $this->get('/free-exams')->assertNotFound();
    }

    public function test_public_exam_management_requires_its_own_admin_permission(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $teacher = User::factory()->create();
        $teacher->roles()->attach(Role::query()->where('name', 'teacher')->value('id'));

        $this->actingAs($teacher)
            ->get('/admin/public-exams')
            ->assertForbidden();

        $admin = User::factory()->create();
        $admin->roles()->attach(Role::query()->where('name', 'admin')->value('id'));

        $this->actingAs($admin)
            ->withoutVite()
            ->get('/admin/public-exams')
            ->assertOk();
    }

    public function test_public_exam_content_management_uses_the_existing_admin_authentication(): void
    {
        $this->get('/admin/public-exams')
            ->assertRedirect(route('login'));
    }

    private function createExam(array $overrides = []): PublicExam
    {
        $exam = PublicExam::query()->create(array_merge([
            'title' => 'آزمون تستی',
            'slug' => 'test-exam',
            'subject' => 'منطق',
            'level' => 2,
            'duration_minutes' => 10,
            'pass_percent' => 50,
            'negative_marking' => false,
            'shuffle' => false,
            'is_published' => true,
        ], $overrides));

        $question = $exam->questions()->create([
            'body' => 'حاصل ۲ + ۲ کدام است؟',
            'explanation' => 'جمع دو و دو برابر چهار است.',
            'points' => 1,
            'position' => 0,
        ]);

        foreach ([
            ['body' => '۴', 'is_correct' => true, 'position' => 0],
            ['body' => '۳', 'is_correct' => false, 'position' => 1],
            ['body' => '۵', 'is_correct' => false, 'position' => 2],
            ['body' => '۶', 'is_correct' => false, 'position' => 3],
        ] as $option) {
            $question->options()->create($option);
        }

        return $exam;
    }
}
