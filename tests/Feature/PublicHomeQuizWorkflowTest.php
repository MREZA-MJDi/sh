<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicHomeQuizWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_homepage_renders_the_quiz_and_its_public_api_endpoints(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('id="homeQuiz"', false)
            ->assertSee('data-question-url="'.url('/home-quiz/question').'"', false)
            ->assertSee('data-answer-url="'.route('public.home.quiz.answer').'"', false);
    }

    public function test_quiz_question_endpoint_returns_options_without_leaking_the_answer(): void
    {
        $this->getJson(route('public.home.quiz.question', ['step' => 1]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('question.id', 'q1')
            ->assertJsonStructure(['question' => ['category', 'question', 'options']])
            ->assertJsonMissingPath('question.answer')
            ->assertJsonMissingPath('question.explanation');
    }

    public function test_quiz_answer_endpoint_checks_the_selected_option_and_returns_feedback(): void
    {
        $this->postJson(route('public.home.quiz.answer'), [
            'step' => 1,
            'answer' => 'B',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('correct', true)
            ->assertJsonPath('is_last', false);

        $this->postJson(route('public.home.quiz.answer'), [
            'step' => 1,
            'answer' => 'A',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('correct', false)
            ->assertJsonPath('correct_answer', 'B');
    }

    public function test_quiz_rejects_unknown_steps_and_invalid_answers(): void
    {
        $this->getJson(route('public.home.quiz.question', ['step' => 6]))->assertNotFound();

        $this->postJson(route('public.home.quiz.answer'), [
            'step' => 6,
            'answer' => 'Z',
        ])->assertUnprocessable();
    }
}
