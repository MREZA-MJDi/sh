<?php

namespace Tests\Feature;

use App\Exceptions\ContractPendingException;
use App\Services\Learning\LearningProvider;
use App\Services\Learning\PendingContractProvider;
use Tests\TestCase;

final class LearningProviderFallbackTest extends TestCase
{
    public function test_learning_provider_is_bound_to_the_safe_pending_contract_adapter(): void
    {
        $provider = app(LearningProvider::class);

        $this->assertInstanceOf(PendingContractProvider::class, $provider);

        $this->expectException(ContractPendingException::class);
        $provider->issueLicense('external-course-1', 'دانش‌آموز آزمایشی', 'student-1');
    }

    public function test_video_upload_does_not_fake_a_success_before_the_provider_contract_exists(): void
    {
        $this->expectException(ContractPendingException::class);

        app(LearningProvider::class)->uploadVideo(storage_path('app/private/not-used.mp4'), 'ویدیوی آزمایشی');
    }
}
