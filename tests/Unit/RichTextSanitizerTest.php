<?php

namespace Tests\Unit;

use App\Support\RichTextSanitizer;
use PHPUnit\Framework\TestCase;

final class RichTextSanitizerTest extends TestCase
{
    public function test_it_preserves_supported_formatting_and_removes_executable_markup(): void
    {
        $html = '<p style="color: #123456; font-size: 18px" onclick="alert(1)">'
            .'<strong>متن مهم</strong><a href="javascript:alert(1)">پیوند</a>'
            .'<script>alert(1)</script></p>';

        $clean = (new RichTextSanitizer())->sanitize($html);

        self::assertStringContainsString('<strong>متن مهم</strong>', $clean);
        self::assertStringContainsString('color: #123456', $clean);
        self::assertStringNotContainsString('onclick', $clean);
        self::assertStringNotContainsString('<script', $clean);
        self::assertStringNotContainsString('javascript:', $clean);
    }

    public function test_it_returns_an_empty_string_for_empty_content(): void
    {
        self::assertSame('', (new RichTextSanitizer())->sanitize('  '));
    }
}
