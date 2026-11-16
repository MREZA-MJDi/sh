<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
