<?php

namespace Tests\Feature;

use App\Http\Controllers\PublicSiteController;
use App\Http\Controllers\Student\CourseController as StudentCourseController;
use App\Http\Controllers\Student\JoinClassSessionController;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

final class LearningWebRouteIntegrationTest extends TestCase
{
    public function test_public_course_detail_has_one_canonical_web_route(): void
    {
        $routes = RouteFacade::getRoutes();
        $publicCourseRoutes = collect($routes)->filter(
            fn (Route $route): bool => $route->uri() === 'courses/{slug}' && in_array('GET', $route->methods(), true)
        );

        $this->assertCount(1, $publicCourseRoutes);
        $this->assertSame(
            PublicSiteController::class.'@course',
            $routes->getByName('public.courses.show')->getActionName()
        );
    }

    public function test_student_learning_routes_are_loaded_from_the_learning_route_file(): void
    {
        $routes = RouteFacade::getRoutes();

        $this->assertSame(
            StudentCourseController::class.'@index',
            $routes->getByName('student.courses.index')->getActionName()
        );
        $this->assertSame(
            JoinClassSessionController::class,
            $routes->getByName('student.class-sessions.join')->getActionName()
        );
        $this->assertNotNull($routes->getByName('student.courses.enroll-free'));
        $this->assertNotNull($routes->getByName('student.resources.download'));
        $this->assertNotNull($routes->getByName('guardian.resources.download'));
        $this->assertNotNull($routes->getByName('admin.licenses.retry'));
        $this->assertNotNull($routes->getByName('admin.lessons.video.retry'));
    }
}
