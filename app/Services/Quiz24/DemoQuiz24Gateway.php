<?php

namespace App\Services\Quiz24;

use App\Services\Integrations\Contracts\IntegrationHealth;
use App\Services\Quiz24\Contracts\Quiz24ExamCommand;
use App\Services\Quiz24\Contracts\Quiz24ExamData;
use App\Services\Quiz24\Contracts\Quiz24Gateway;
use App\Services\Quiz24\Contracts\Quiz24StudentLaunchCommand;
use App\Services\Quiz24\Contracts\Quiz24StudentLaunchData;

/** شبیه‌ساز نمایشی؛ هیچ درخواست بیرونی نمی‌فرستد. */
class DemoQuiz24Gateway implements Quiz24Gateway
{
    public function health(): IntegrationHealth
    {
        return new IntegrationHealth(true, 'demo', 'حالت نمایشی فعال است؛ اتصال واقعی Quiz24 برقرار نیست.', 'demo');
    }

    public function listExams(): iterable
    {
        return [new Quiz24ExamData('demo-exam-1', 'آزمون نمایشی', null, null, null, 'active')];
    }

    public function createExam(Quiz24ExamCommand $command): Quiz24ExamData
    {
        return new Quiz24ExamData('demo-exam-'.$command->localExamId, $command->title, $command->description, $command->startsAt, $command->endsAt, 'active');
    }

    public function listResults(string $externalExamId): iterable
    {
        return [];
    }

    public function createStudentLaunchUrl(Quiz24StudentLaunchCommand $command): Quiz24StudentLaunchData
    {
        return new Quiz24StudentLaunchData(url('/demo/quiz24/'.rawurlencode($command->externalExamId)), now()->addHour()->toIso8601String(), 'demo');
    }
}
