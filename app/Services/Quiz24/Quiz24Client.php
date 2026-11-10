<?php

namespace App\Services\Quiz24;

use App\Models\Quiz24Setting;
use App\Services\Integrations\IntegrationException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class Quiz24Client
{
    public function __construct(private readonly Quiz24Setting $settings)
    {
    }

    public function get(string $endpoint): array
    {
        $this->assertConfigured();
        $this->assertRelativeEndpoint($endpoint);

        $header = trim((string) $this->settings->api_token_header);
        $prefix = trim((string) $this->settings->api_token_prefix);

        if (! preg_match('/^[A-Za-z0-9-]{1,100}$/', $header)) {
            throw new IntegrationException('نام هدر احراز هویت Quiz24 باید مطابق قرارداد API تنظیم شود.');
        }

        $headers = [
            $header => trim($prefix.' '.$this->settings->api_token),
        ];

        try {
            $response = Http::acceptJson()
                ->withHeaders($headers)
                ->connectTimeout(8)
                ->timeout(25)
                ->withOptions(['verify' => true])
                ->get(rtrim($this->settings->api_base_url, '/').'/'.ltrim($endpoint, '/'));
        } catch (ConnectionException $exception) {
            throw new IntegrationException('ارتباط با سرویس Quiz24 برقرار نشد.', previous: $exception);
        }

        if ($response->failed()) {
            throw new IntegrationException('درخواست Quiz24 ناموفق بود. کد پاسخ: '.$response->status());
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new IntegrationException('پاسخ Quiz24 به‌صورت JSON معتبر دریافت نشد.');
        }

        return $data;
    }

    private function assertConfigured(): void
    {
        if (! $this->settings->is_enabled ||
            blank($this->settings->api_base_url) ||
            blank($this->settings->api_token) ||
            blank($this->settings->api_token_header)) {
            throw new IntegrationException('تنظیمات اتصال Quiz24 کامل یا فعال نیست.');
        }

        $url = parse_url($this->settings->api_base_url);
        $host = strtolower($url['host'] ?? '');

        if (($url['scheme'] ?? '') !== 'https' || ! $this->isAllowedHost($host)) {
            throw new IntegrationException('آدرس API باید HTTPS و متعلق به دامنه Quiz24 یا زیردامنه تأییدشده آن باشد.');
        }
    }

    private function isAllowedHost(string $host): bool
    {
        return $host === 'quiz24.ir'
            || $host === 'www.quiz24.ir'
            || str_ends_with($host, '.quiz24.ir');
    }

    private function assertRelativeEndpoint(string $endpoint): void
    {
        if ($endpoint === '' ||
            ! str_starts_with($endpoint, '/') ||
            str_starts_with($endpoint, '//') ||
            str_contains($endpoint, '://') ||
            str_contains($endpoint, '..')) {
            throw new IntegrationException('مسیر API باید یک مسیر نسبی معتبر باشد و از میزبان ثبت‌شده خارج نشود.');
        }
    }
}
