<?php

namespace Tests\Unit;

use App\Services\Learning\LocalVideoProcessor;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class LocalVideoProcessorTest extends TestCase
{
    public function test_viewer_render_uses_its_own_queue(): void
    {
        $job = new \App\Jobs\Learning\RenderViewerVideo('source.mp4', 'viewer-videos/example.mp4', 1);
        $this->assertSame('video', $job->connection);
        $this->assertSame('viewer-video', $job->queue);
    }
    public function test_remux_requires_browser_compatible_streams(): void
    {
        $processor = new LocalVideoProcessor;
        $info = [
            'video' => ['codec_name' => 'h264', 'pix_fmt' => 'yuv420p', 'profile' => 'High',
                'level' => 31, 'width' => 1280, 'height' => 720],
            'audio' => ['codec_name' => 'aac', 'profile' => 'LC', 'channels' => 2],
        ];
        $this->assertTrue($processor->canRemux($info, 720));
        $this->assertFalse($processor->canRemux($info, 480));
        $this->assertFalse($processor->canRemux(array_replace($info, [
            'video' => array_replace($info['video'], ['codec_name' => 'vp9']),
        ]), 720));
        $this->assertFalse($processor->canRemux(array_replace($info, [
            'audio' => ['codec_name' => 'opus', 'channels' => 2],
        ]), 720));
        $this->assertTrue($processor->canRemux(array_replace($info, ['audio' => null]), 720));
    }

    public function test_compatible_mp4_is_remuxed_without_changing_video_packets(): void
    {
        foreach (['ffmpeg', 'ffprobe'] as $binary) {
            $check = new Process([$binary, '-version']);
            if ($check->run() !== 0) {
                $this->markTestSkipped($binary.' is unavailable.');
            }
        }

        $directory = sys_get_temp_dir().'/learning-media-'.Str::uuid();
        File::makeDirectory($directory);
        try {
            $source = $directory.'/source.mp4';
            $output = $directory.'/output.mp4';
            (new Process(['ffmpeg', '-v', 'error', '-f', 'lavfi', '-i', 'testsrc2=size=320x240:rate=25',
                '-t', '1', '-c:v', 'libx264', '-threads:v', '1', '-pix_fmt', 'yuv420p', $source]))->mustRun();
            $processor = new LocalVideoProcessor;
            $info = $processor->prepare($source, $output, 720, 26, 128, 30);

            $this->assertSame('h264', $info['video']['codec_name']);
            $this->assertSame(320, $info['video']['width']);
            $this->assertSame($this->packetHash($source), $this->packetHash($output));
        } finally {
            File::deleteDirectory($directory);
        }
    }

    private function packetHash(string $path): string
    {
        $process = new Process(['ffprobe', '-v', 'error', '-select_streams', 'v:0',
            '-show_packets', '-show_data_hash', 'sha256', '-show_entries', 'packet=data_hash',
            '-of', 'compact=p=0:nk=1', $path]);
        $process->mustRun();

        return trim($process->getOutput());
    }
}
