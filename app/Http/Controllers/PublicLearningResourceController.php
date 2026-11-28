<?php

namespace App\Http\Controllers;

use App\Models\LearningResource;
use App\Services\Learning\DocumentReader;
use App\Models\LearningResourceAccessLog;
use App\Services\LearningResourceAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;

final class PublicLearningResourceController extends Controller
{
    public function download(
        Request $request,
        LearningResource $resource,
        LearningResourceAccessService $access, DocumentReader $reader
    ): Response {
        abort_unless($request->user()?->is_active, 403);
        $access->authorizePublicGuest($resource);

        abort_unless(Storage::disk('local')->exists($resource->file_path), 404);

        if ($request->query('render') !== '1') {
            LearningResourceAccessLog::query()->create([
                'learning_resource_id' => $resource->id,
                'user_id' => $request->user()->id,
                'student_id' => null,
                'event_type' => 'view',
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 2000),
                'accessed_at' => now(),
            ]);
        }

        return $reader->show($request, $resource->file_path, $resource->title);
    }
}
