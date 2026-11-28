<?php

namespace App\Services\Learning;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;

final class DocumentReader
{
    public function show(Request $request, string $file, string $title): Response
    {
        abort_unless($request->user()?->is_active, 403);
        $watermark = ContentWatermark::identity($request->user());
        $disk = Storage::disk('local');
        abort_unless($disk->exists($file), 404);

        $path = $disk->path($file);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $headers = [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Cross-Origin-Resource-Policy' => 'same-origin',
            'X-Frame-Options' => 'SAMEORIGIN',
        ];

        $page = filter_var($request->query('page', '1'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 100000],
        ]);
        abort_if($page === false, 404);

        $pages = match ($mime) {
            'application/pdf' => $this->pageCount($path),
            'image/jpeg', 'image/png' => 1,
            default => 0,
        };

        if ($request->query('render') === '1') {
            abort_if($request->header('Sec-Fetch-Mode') === 'navigate', 403);
            abort_unless($page <= $pages, 404);
            $image = $mime === 'application/pdf'
                ? $this->run('pdftoppm', ['-f', (string) $page, '-l', (string) $page, '-scale-to', '1600', '-singlefile', '-png', $path])
                : $this->renderImage($path);
            abort_unless(str_starts_with($image, "\x89PNG\r\n\x1a\n"), 422);

            return response(ContentWatermark::markImage($image, $watermark), 200, [...$headers, 'Content-Type' => 'image/png']);
        }

        abort_if($pages > 0 && $page > $pages, 404);

        $text = $mime === 'text/plain' ? file_get_contents($path, false, null, 0, 128 * 1024) : null;

        return response()->view('learning.document-reader', [
            'title' => $title,
            'watermark' => $watermark,
            'page' => $page,
            'pages' => $pages,
            'text' => $text === false ? null : $text,
            'textTruncated' => $text !== null && filesize($path) > 128 * 1024,
            'renderUrl' => $request->fullUrlWithQuery(['page' => $page, 'render' => 1]),
            'previousUrl' => $request->fullUrlWithQuery(['page' => $page - 1]),
            'nextUrl' => $request->fullUrlWithQuery(['page' => $page + 1]),
        ], 200, $headers);
    }

    private function pageCount(string $path): int
    {
        $info = $this->run('pdfinfo', [$path]);
        abort_unless(preg_match('/^Pages:\s+([1-9]\d*)\s*$/m', $info, $matches), 422);

        return (int) $matches[1];
    }

    private function run(string $program, array $arguments): string
    {
        $bin = rtrim((string) config('learning.document_reader_bin'), '/\\');
        $process = new Process(
            [$bin === '' ? $program : $bin.DIRECTORY_SEPARATOR.$program, ...$arguments],
            null,
            ['LC_ALL' => 'C'],
            null,
            15,
        );

        try {
            $process->run();
        } catch (ExceptionInterface $exception) {
            report($exception);
            abort(503, 'نمایش سند موقتاً در دسترس نیست.');
        }

        abort_unless($process->isSuccessful(), $process->getExitCode() === 127 ? 503 : 422);

        return $process->getOutput();
    }

    private function renderImage(string $path): string
    {
        abort_unless(extension_loaded('gd'), 503);
        $info = @getimagesize($path);
        abort_unless($info && $info[0] * $info[1] <= 12000000, 422);

        $image = $info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($path) : @imagecreatefromjpeg($path);
        abort_unless($image, 422);
        imagesavealpha($image, true);

        ob_start();
        try {
            imagepng($image);
            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
