<?php

namespace App\Services\Learning;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Shared local media pipeline. Storage, approval and publication remain
 * responsibilities of the caller; the provider gateway never uses this encoder.
 */
final class LocalVideoProcessor
{
    public function prepare(string $source, string $output, int $height, int $crf, int $audioKbps, int $timeout = 3300, ?string $watermark = null): array
    {
        $info = $this->probe($source);
        $threads = (string) config('learning.video_threads', 1);
        $command = [
            'ffmpeg', '-nostdin', '-hide_banner', '-loglevel', 'error', '-y',
            '-threads', $threads, '-filter_threads', $threads, '-i', $source,
            '-map', '0:V:0', '-map', '0:a:0?', '-sn', '-dn',
            '-map_metadata', '-1', '-map_chapters', '-1',
        ];

        $filter = 'scale=w=min(1920\,iw):h=min('.$height.'\,ih):force_original_aspect_ratio=decrease:force_divisible_by=2';
        if ($watermark !== null) {
            $label = preg_replace('/[^a-z0-9@._+ -]/i', '_', $watermark);
            $draw = ',drawtext=text=SHEYKHAN '.$label.':expansion=none'
                .':fontcolor=white@0.44:fontsize=min(h/20\,w/'.max(20, strlen($label) + 10).')'
                .':borderw=1:bordercolor=black@0.36';
            foreach ([['.22', '.28'], ['.50', '.50'], ['.82', '.71']] as [$x, $y]) {
                $filter .= $draw.':x=max(0\,min(w-tw\,w*'.$x.'-tw/2)):y=h*'.$y.'-th/2';
            }
        }

        $audio = $info['audio'];
        $copyAudio = $watermark !== null
            && ($audio['codec_name'] ?? null) === 'aac'
            && ($audio['profile'] ?? null) === 'LC'
            && in_array((int) ($audio['channels'] ?? 0), [1, 2], true)
            && (int) ($audio['bit_rate'] ?? 0) > 0
            && (int) $audio['bit_rate'] <= $audioKbps * 1000;

        $encoding = [
            '-vf', $filter,
            '-c:v', 'libx264', '-threads:v', $threads,
            '-preset', 'veryfast', '-crf', (string) $crf, '-pix_fmt', 'yuv420p',
            ...($copyAudio ? ['-c:a', 'copy'] : ['-c:a', 'aac', '-b:a', $audioKbps.'k', '-ac', '2']),
        ];

        $deadline = microtime(true) + $timeout;
        if ($watermark === null && $this->canRemux($info, $height)) {
            $copy = new Process([...$command, '-c', 'copy', '-movflags', '+faststart', '-f', 'mp4', $output]);
            $copy->setTimeout(max(1, $deadline - microtime(true)));
            $copy->run();

            if ($copy->isSuccessful()) {
                try {
                    return $this->validateOutput($output);
                } catch (RuntimeException) {
                    // A valid input can still produce an incompatible container.
                }
            }
        }

        $remaining = $deadline - microtime(true);
        if ($remaining <= 0) {
            throw new RuntimeException('Video processing time budget exhausted.');
        }

        $encode = new Process([...$command, ...$encoding, '-movflags', '+faststart', '-f', 'mp4', $output]);
        $encode->setTimeout($remaining)->mustRun();

        return $this->validateOutput($output);
    }

    public function canRemux(array $info, int $maxHeight): bool
    {
        $video = $info['video'];
        $audio = $info['audio'];
        $width = (int) ($video['width'] ?? 0);
        $height = (int) ($video['height'] ?? 0);

        return ($video['codec_name'] ?? null) === 'h264'
            && ($video['pix_fmt'] ?? null) === 'yuv420p'
            && in_array($video['profile'] ?? null, ['Baseline', 'Constrained Baseline', 'Main', 'High'], true)
            && (int) ($video['level'] ?? 0) > 0
            && (int) ($video['level'] ?? 0) <= 42
            && $width > 0 && $width <= 1920 && $width % 2 === 0
            && $height > 0 && $height <= $maxHeight && $height % 2 === 0
            && ($audio === null || (
                ($audio['codec_name'] ?? null) === 'aac'
                && ($audio['profile'] ?? null) === 'LC'
                && (int) ($audio['channels'] ?? 0) >= 1
                && (int) ($audio['channels'] ?? 0) <= 2
            ));
    }

    public function poster(string $source, string $output): bool
    {
        $threads = (string) config('learning.video_threads', 1);

        foreach (['1', '0'] as $second) {
            $process = new Process([
                'ffmpeg', '-nostdin', '-hide_banner', '-loglevel', 'error', '-y',
                '-threads', $threads, '-filter_threads', $threads,
                '-ss', $second, '-i', $source,
                '-frames:v', '1', '-threads:v', $threads, '-q:v', '4', $output,
            ]);
            $process->setTimeout(60)->run();
            clearstatcache(true, $output);

            if ($process->isSuccessful() && is_file($output) && filesize($output) > 0) {
                return true;
            }
        }

        return false;
    }

    private function validateOutput(string $path): array
    {
        clearstatcache(true, $path);
        if (! is_file($path) || filesize($path) < 1024) {
            throw new RuntimeException('خروجی ویدیو معتبر نیست.');
        }

        return $this->probe($path);
    }

    private function probe(string $path): array
    {
        $process = new Process([
            'ffprobe', '-v', 'error',
            '-show_entries', 'format=duration:stream=codec_type,codec_name,profile,level,pix_fmt,width,height,channels:stream_disposition=attached_pic',
            '-of', 'json', $path,
        ]);
        $process->setTimeout(30)->mustRun();
        $data = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $video = collect($data['streams'] ?? [])->first(fn (array $stream): bool => ($stream['codec_type'] ?? null) === 'video' && ! ($stream['disposition']['attached_pic'] ?? false));
        $audio = collect($data['streams'] ?? [])->firstWhere('codec_type', 'audio');
        $duration = (float) ($data['format']['duration'] ?? 0);

        if (! $video || (int) ($video['width'] ?? 0) < 1 || (int) ($video['height'] ?? 0) < 1 || $duration <= 0) {
            throw new RuntimeException('فایل فاقد جریان ویدیویی یا مدت معتبر است.');
        }

        return ['video' => $video, 'audio' => $audio, 'duration' => $duration];
    }
}
