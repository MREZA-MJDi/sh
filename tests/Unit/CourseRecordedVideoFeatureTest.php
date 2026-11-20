<?php

namespace Tests\Unit;

use App\Models\Course;
use PHPUnit\Framework\TestCase;

class CourseRecordedVideoFeatureTest extends TestCase
{
    public function test_legacy_internal_course_keeps_recorded_video_independent_from_spotplayer(): void
    {
        $course = new Course([
            'delivery_type' => 'internal',
            'learning_features' => null,
        ]);

        $this->assertTrue($course->featureEnabled('recorded_video'));
    }

    public function test_legacy_spotplayer_course_keeps_recorded_video_enabled(): void
    {
        $course = new Course([
            'delivery_type' => 'spotplayer',
            'learning_features' => null,
        ]);

        $this->assertTrue($course->featureEnabled('recorded_video'));
    }

    public function test_explicit_recorded_video_feature_setting_overrides_legacy_default(): void
    {
        $disabled = new Course([
            'delivery_type' => 'spotplayer',
            'learning_features' => ['recorded_video' => false],
        ]);
        $enabled = new Course([
            'delivery_type' => 'internal',
            'learning_features' => ['recorded_video' => true],
        ]);

        $this->assertFalse($disabled->featureEnabled('recorded_video'));
        $this->assertTrue($enabled->featureEnabled('recorded_video'));
    }
}
