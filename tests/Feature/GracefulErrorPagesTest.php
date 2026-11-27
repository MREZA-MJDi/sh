<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class GracefulErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_common_http_error_pages_render_branded_persian_fallbacks(): void
    {
        foreach ([
            '403' => 'این بخش برای حساب شما در دسترس نیست',
            '404' => 'این صفحه پیدا نشد',
            '419' => 'نشست شما منقضی شده است',
            '429' => 'کمی سریع‌تر از حد مجاز درخواست فرستادید',
            '500' => 'در انجام این درخواست مشکلی پیش آمد',
            '503' => 'این سرویس موقتاً در دسترس نیست',
        ] as $status => $heading) {
            $html = view('errors.'.$status)->render();

            $this->assertStringContainsString('آکادمی شیخان', $html);
            $this->assertStringContainsString($heading, $html);
            $this->assertStringNotContainsString('Stack trace', $html);
        }
    }

    public function test_form_feedback_explains_validation_and_keeps_valid_fields(): void
    {
        $this->withoutVite();
        $this->followingRedirects()
            ->from(route('public.contact'))
            ->post(route('public.contact.store'), ['name' => 'ا', 'contact' => 'qa@example.test'])
            ->assertOk()
            ->assertSee('«نام و نام خانوادگی» باید دست‌کم 2 نویسه باشد.')
            ->assertSee('وارد کردن «متن پیام» الزامی است.')
            ->assertSee('value="qa@example.test"', false)
            ->assertDontSee('validation.required');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_http_errors_keep_the_status_and_explain_the_next_action(): void
    {
        config(['app.debug' => false]);
        Route::get('/test-invalid-action', fn () => abort(422, 'این گفتگو بسته شده است.'));
        Route::get('/test-large-upload', fn () => abort(413));
        Route::get('/test-internal-error', fn () => throw new \RuntimeException('internal-detail-marker'));

        $this->get('/test-invalid-action')->assertUnprocessable()->assertSee('این گفتگو بسته شده است.');
        $this->get('/missing-panel-test')->assertNotFound()->assertSee('ممکن است پیوند تغییر کرده باشد');
        $this->get('/test-large-upload')->assertStatus(413)->assertSee('فایل کوچک‌تری انتخاب کنید');
        $this->get('/test-internal-error')->assertStatus(500)
            ->assertSee('سرور نتوانست نتیجهٔ درخواست را نمایش دهد')
            ->assertDontSee('internal-detail-marker');
    }

}
