<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicExamFeatureTest extends TestCase
{
    public function test_public_exam_entry_is_disabled_by_default(): void
    {
        config(['public_exams.enabled' => false]);

        $this->withoutVite()
            ->get('/free-exams')
            ->assertNotFound();
    }

    public function test_public_exam_content_management_uses_the_existing_admin_authentication(): void
    {
        $this->get('/admin/public-exams')
            ->assertRedirect(route('login'));
    }
}
