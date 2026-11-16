<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\LearningResource;
use Illuminate\Http\Request;

final class LearningResourceController extends Controller
{
    /**
     * Course files are stored privately and are never exposed as downloadable
     * responses. Playback/preview must use a separately protected delivery
     * provider; this legacy URL intentionally remains a hard 404.
     */
    public function download(Request $request, LearningResource $resource): never
    {
        abort(404);
    }
}
