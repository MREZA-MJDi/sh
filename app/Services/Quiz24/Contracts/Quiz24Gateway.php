<?php

namespace App\Services\Quiz24\Contracts;

use App\Services\Integrations\Contracts\IntegrationHealth;

interface Quiz24Gateway
{
    public function health(): IntegrationHealth;

    /** @return iterable<Quiz24ExamData> */
    public function listExams(): iterable;

    public function createExam(Quiz24ExamCommand $command): Quiz24ExamData;

    /** @return iterable<Quiz24ResultData> */
    public function listResults(string $externalExamId): iterable;

    public function createStudentLaunchUrl(Quiz24StudentLaunchCommand $command): Quiz24StudentLaunchData;
}
