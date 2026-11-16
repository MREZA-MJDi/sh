<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\LearningResource;
use App\Models\LearningResourceAccessLog;
use App\Services\LearningResourceAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class LearningResourceController extends Controller
{
    public function download(
        Request $request,
        LearningResource $resource,
        LearningResourceAccessService $access
    ): StreamedResponse {
        $student = $access->resolveStudent($request->user(), $request->integer('child'));
        $resource = LearningResource::query()->whereKey($resource->getKey())->firstOrFail();
        $access->authorizeDownload($student, $resource);

        abort_unless(Storage::disk('local')->exists($resource->file_path), 404);
        abort_unless($resource->downloadable || $resource->kind === 'pdf', 404);

        $eventType = $resource->downloadable ? 'download' : 'view';
        LearningResourceAccessLog::query()->create([
            'learning_resource_id' => $resource->id,
            'user_id' => $request->user()->id,
            'student_id' => $student->id,
            'event_type' => $eventType,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 2000),
            'accessed_at' => now(),
        ]);

        if (! $resource->downloadable) {
            abort_unless($resource->kind === 'pdf', 404);

            return Storage::disk('local')->response(
                $resource->file_path,
                basename($resource->file_path),
                ['X-Content-Type-Options' => 'nosniff'],
                'inline'
            );
        }

        return Storage::disk('local')->download($resource->file_path);
    }
}