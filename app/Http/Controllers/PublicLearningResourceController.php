<?php

namespace App\Http\Controllers;

use App\Models\LearningResource;
use Illuminate\Http\Request;

final class PublicLearningResourceController extends Controller
{
    /**
     * Public course-file delivery is disabled. Private learning assets must
     * never be streamed or downloaded through a public endpoint.
     */
    public function download(Request $request, LearningResource $resource): never
    {
        abort(404);
    }
}
