<?php

namespace App\Services\Learning;

interface LearningProvider
{
    /**
     * صدور لایسنس برای یک دانش‌آموز.
     *
     * @return array{id: string, key: string}
     * @throws \App\Exceptions\ContractPendingException
     */
    public function issueLicense(string $externalCourseId, string $holderName, string $watermark): array;

    /**
     * ارسال فایل ویدیو به سرویس‌دهنده و برگرداندن شناسه ویدیو.
     *
     * @throws \App\Exceptions\ContractPendingException
     */
    public function uploadVideo(string $absolutePath, string $title): string;
}
