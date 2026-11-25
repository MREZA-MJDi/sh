<?php

namespace Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Tests\TestCase;

final class SharedPaginationTest extends TestCase
{
    public function test_length_aware_pagination_uses_shared_persian_markup_and_preserves_filters(): void
    {
        app()->setLocale('fa');
        $paginator = new LengthAwarePaginator(range(19, 36), 56, 18, 2, ['path' => '/admin/lms']);
        $paginator->appends(['status' => 'published']);
        $html = $paginator->links()->toHtml();

        $this->assertStringContainsString('class="sh-pagination"', $html);
        $this->assertStringContainsString('dir="rtl"', $html);
        $this->assertStringContainsString('نمایش 19 تا 36 از 56 مورد', $html);
        $this->assertStringContainsString('قبلی', $html);
        $this->assertStringContainsString('بعدی', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('status=published&amp;page=3', $html);
        $this->assertStringNotContainsString('pagination.previous', $html);
        $this->assertStringNotContainsString('<svg', $html);
    }

    public function test_simple_paginator_uses_same_controls_without_requesting_total(): void
    {
        app()->setLocale('fa');
        $paginator = new Paginator([1, 2, 3], 2, 1, ['path' => '/student/videos', 'pageName' => 'videos']);
        $html = $paginator->links()->toHtml();

        $this->assertStringContainsString('class="sh-pagination"', $html);
        $this->assertStringContainsString('aria-disabled="true"', $html);
        $this->assertStringContainsString('videos=2', $html);
        $this->assertStringNotContainsString('sh-pagination-summary', $html);
    }

    public function test_single_page_does_not_render_empty_navigation(): void
    {
        $paginator = new LengthAwarePaginator([1], 1, 18, 1);

        $this->assertSame('', trim($paginator->links()->toHtml()));
    }
}
