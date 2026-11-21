<?php

namespace Tests\Feature;

use Tests\TestCase;

final class TeacherTopbarInteractionAssetsTest extends TestCase
{
    public function test_multi_element_teacher_controls_use_query_selector_all(): void
    {
        $script = file_get_contents(resource_path('js/teacher/teacher.js'));

        self::assertIsString($script);
        self::assertStringContainsString('var paletteButtons = $$(\'[data-pal]\');', $script);
        self::assertStringContainsString('var topbarMenus = $$(\'.teacher-menu\');', $script);
        self::assertStringContainsString('var searchableItems = $$(searchableSelector).filter(', $script);
        self::assertStringContainsString('$(\'[data-search-route]\', searchLinks).forEach(', $script);
        self::assertStringContainsString('var focusable = $$(\'a[href]', $script);
        self::assertStringNotContainsString('var paletteButtons = $(\'[data-pal]\');', $script);
        self::assertStringNotContainsString('var topbarMenus = $(\'.teacher-menu\');', $script);
    }
}
