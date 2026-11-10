<?php

namespace App\Services\Quiz24;

use App\Services\Integrations\Contracts\IntegrationHealth;
use App\Services\Integrations\IntegrationException;
use App\Services\Quiz24\Contracts\Quiz24ExamCommand;
use App\Services\Quiz24\Contracts\Quiz24ExamData;
use App\Services\Quiz24\Contracts\Quiz24Gateway;
use App\Services\Quiz24\Contracts\Quiz24ResultData;
use App\Services\Quiz24\Contracts\Quiz24StudentLaunchCommand;
use App\Services\Quiz24\Contracts\Quiz24StudentLaunchData;

class UnavailableQuiz24Gateway implements Quiz24Gateway
{
    public function health(): IntegrationHealth
    {
        return new IntegrationHealth(
            false,
            'contract_missing',
            'قرارداد رسمی وب‌سرویس Quiz24 هنوز از ارائه‌دهنده دریافت نشده است.'
        );
    }

    public function listExams(): iterable
    {
        throw new IntegrationException('دریافت آزمون‌ها تا دریافت مستندات و دسترسی API Quiz24 غیرفعال است.');
    }

    public function createExam(Quiz24ExamCommand $command): Quiz24ExamData
    {
        throw new IntegrationException('ساخت آزمون در Quiz24 تا دریافت قرارداد رسمی API غیرفعال است.');
    }

    public function listResults(string $externalExamId): iterable
    {
        throw new IntegrationException('همگام‌سازی نتایج تا دریافت مستندات و دسترسی API Quiz24 غیرفعال است.');
    }

    public function createStudentLaunchUrl(Quiz24StudentLaunchCommand $command): Quiz24StudentLaunchData
    {
        throw new IntegrationException('ورود یکپارچه Quiz24 تا دریافت قرارداد رسمی SSO غیرفعال است.');
    }
}
