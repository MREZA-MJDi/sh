<?php

namespace Tests\Unit;

use App\Services\LegacyWordPressPassword;
use PHPUnit\Framework\TestCase;

final class LegacyWordPressPasswordTest extends TestCase
{
    public function test_phpass_hash_is_verified_and_bad_passwords_are_rejected(): void
    {
        $service = new LegacyWordPressPassword();
        $hash = '$P$B12345678KrcV6c7XbhUsava2BrG7b.';

        $this->assertTrue($service->isLegacyHash($hash));
        $this->assertTrue($service->supportsHashFormat($hash));
        $this->assertTrue($service->verify('legacy-import-password', $hash));
        $this->assertFalse($service->verify('incorrect-password', $hash));
        $this->assertFalse($service->supportsHashFormat('$P$short'));
    }

    public function test_wrapped_wordpress_bcrypt_hash_is_verified(): void
    {
        $plain = 'legacy-wp-bcrypt-test-password';
        $prehashed = base64_encode(hash_hmac('sha384', $plain, 'wp-sha384', true));
        $hash = '$wp'.password_hash($prehashed, PASSWORD_BCRYPT);
        $service = new LegacyWordPressPassword();

        $this->assertTrue($service->isLegacyHash($hash));
        $this->assertTrue($service->supportsHashFormat($hash));
        $this->assertTrue($service->verify($plain, $hash));
        $this->assertFalse($service->verify('wrong-test-password', $hash));
    }
}
