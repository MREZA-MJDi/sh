<?php

namespace Tests\Unit;

use App\Support\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    public function test_formats_iranian_mobile_numbers_with_consistent_grouping(): void
    {
        self::assertSame('۰۹۱۲ ۳۴۵ ۶۷۸۹', PhoneNumber::format('09123456789'));
        self::assertSame('۰۹۱۲ ۳۴۵ ۶۷۸۹', PhoneNumber::format('۰۹۱۲-۳۴۵-۶۷۸۹'));
        self::assertSame('۰۹۱۲ ۳۴۵ ۶۷۸۹', PhoneNumber::format('٠٩١٢ ٣٤٥ ٦٧٨٩'));
    }

    public function test_formats_international_iranian_mobile_numbers(): void
    {
        self::assertSame('+۹۸ ۹۱۲ ۳۴۵ ۶۷۸۹', PhoneNumber::format('+989123456789'));
        self::assertSame('+۹۸ ۹۱۲ ۳۴۵ ۶۷۸۹', PhoneNumber::format('00989123456789'));
    }

    public function test_preserves_non_mobile_phone_text_while_localizing_digits(): void
    {
        self::assertSame('۰۲۱-۱۲۳۴۵۶۷۸', PhoneNumber::format('021-12345678'));
        self::assertSame('', PhoneNumber::format(null));
        self::assertSame('', PhoneNumber::format('   '));
    }

    public function test_html_output_is_directionally_isolated_and_escaped(): void
    {
        self::assertSame(
            '<bdi class="phone-number" dir="ltr">۰۹۱۲ ۳۴۵ ۶۷۸۹</bdi>',
            (string) PhoneNumber::html('09123456789')
        );
        self::assertStringContainsString('&lt;script&gt;', (string) PhoneNumber::html('<script>'));
    }
}
