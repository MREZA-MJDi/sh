<?php

namespace App\Console\Commands;

use App\Models\Course;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class PrivatizeCourseTeasers extends Command
{
    protected $signature = 'learning:privatize-teasers';

    protected $description = 'Move existing course teaser videos into private storage';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $failed = false;

        foreach (Course::query()->whereNotNull('teaser_path')->select(['id', 'teaser_path'])->lazyById() as $course) {
            $path = $course->teaser_path;
            $copied = true;

            if ($public->exists($path)) {
                $temporary = $path.'.'.Str::uuid().'.moving';
                $input = $public->readStream($path);
                $copied = false;
                if (is_resource($input)) {
                    try {
                        $copied = $private->put($temporary, $input);
                    } finally {
                        fclose($input);
                    }
                }

                $checksum = $copied ? hash_file('sha256', $public->path($path)) : false;
                $copied = $checksum && $checksum === hash_file('sha256', $private->path($temporary))
                    && $private->move($temporary, $path)
                    && $public->delete($path);
                $private->delete($temporary);
            }

            if (! $copied || ! $private->exists($path)) {
                $this->error("Course {$course->id}: teaser could not be moved to private storage.");
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
