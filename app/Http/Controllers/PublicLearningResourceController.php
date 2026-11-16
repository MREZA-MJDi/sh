<?php

namespace App\Http\Controllers;

use App\Models\LearningResource;
use App\Models\LearningResourceAccessLog;
use App\Services\LearningResourceAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PublicLearningResourceController extends Controller
{
    public function download(
        Request $request,
        LearningResource $resource,
        LearningResourceAccessService $access
    ): StreamedResponse {
        $access->authorizePublicGuest($resource);

        abort_unless(Storage::disk('local')->exists($resource->file_path), 404);

        LearningResourceAccessLog::query()->create([
            'learning_resource_id' => $resource->id,
            'user_id' => null,
            'student_id' => null,
            'event_type' => $resource->downloadable ? 'download' : 'view',
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 2000),
            'accessed_at' => now(),
        ]);

        if (! $resource->downloadable) {
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