<?php

namespace Tests\Feature;

use App\Models\HomeQuizQuestion;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HomeQuizQuestionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->withoutVite();
    }

    private function seoUser(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'seo')->value('id'));

        return $user;
    }

    public function test_seo_user_can_create_and_manage_a_homepage_riddle(): void
    {
        $seo = $this->seoUser();

        $this->actingAs($seo)->get(route('admin.seo.quiz.index'))
            ->assertOk()
            ->assertSee('Quiz & Riddles', false)
            ->assertSee('آزمون و معماهای صفحه اصلی');

        $this->actingAs($seo)->post(route('admin.seo.quiz.store'), [
            'category' => 'معمای منطقی',
            'type' => 'riddle',
            'question' => 'آن چیست که هرچه از آن برداری بزرگ‌تر می‌شود؟',
            'correct_answer' => 'چاله',
            'explanation' => 'با برداشتن خاک، چاله بزرگ‌تر می‌شود.',
            'sort_order' => 6,
            'status' => 'published',
        ])->assertRedirect();

        $riddle = HomeQuizQuestion::query()->where('type', 'riddle')->firstOrFail();

        $this->getJson(route('public.home.quiz.question', 6))
            ->assertOk()
            ->assertJsonPath('question.type', 'riddle')
            ->assertJsonPath('total', 6)
            ->assertJsonMissingPath('question.correct_answer');

        $this->postJson(route('public.home.quiz.answer'), [
            'step' => 6,
            'answer' => 'چاله',
        ])->assertOk()
            ->assertJsonPath('correct', true)
            ->assertJsonPath('is_last', true);

        $this->actingAs($seo)->patch(route('admin.seo.quiz.update', $riddle), [
            'category' => 'معمای روز',
            'type' => 'riddle',
            'question' => 'معمای ویرایش‌شده',
            'correct_answer' => 'گودال',
            'explanation' => 'پاسخ تغییر کرد.',
            'sort_order' => 1,
            'status' => 'draft',
        ])->assertRedirect();

        $this->assertDatabaseHas('home_quiz_questions', [
            'id' => $riddle->id,
            'category' => 'معمای روز',
            'status' => 'draft',
            'correct_answer' => 'گودال',
        ]);

        $this->actingAs($seo)->delete(route('admin.seo.quiz.destroy', $riddle))
            ->assertRedirect();

        $this->assertDatabaseMissing('home_quiz_questions', ['id' => $riddle->id]);
    }

    public function test_homepage_quiz_keeps_the_default_five_questions_and_rejects_unknown_steps(): void
    {
        $this->assertSame(5, HomeQuizQuestion::published()->count());

        $this->getJson(route('public.home.quiz.question', 1))
            ->assertOk()
            ->assertJsonPath('question.id', 'q1')
            ->assertJsonMissingPath('question.answer');

        $this->getJson(route('public.home.quiz.question', 6))->assertNotFound();

        $this->postJson(route('public.home.quiz.answer'), [
            'step' => 6,
            'answer' => 'A',
        ])->assertUnprocessable();
    }
}
