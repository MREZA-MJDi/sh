# Private course video upload operations

The course recording upload path stores assembled source files on Laravel's private `local` disk. It never publishes paid lesson recordings to the public disk.

## Production requirements

1. Install FFmpeg on the application host and confirm `ffmpeg -version` works for the same OS user that runs queue workers.
2. Set `DB_QUEUE_RETRY_AFTER=3700` in production (and rebuild the config cache), then run a persistent Laravel queue worker that consumes the `video` queue. The retry window must be longer than the 3600-second job timeout. Example Supervisor command:

   ```bash
   php artisan queue:work --queue=video,default --sleep=3 --tries=1 --timeout=3600
   ```

   Keep the worker timeout below the process manager's stop timeout. Restart workers after deployment with `php artisan queue:restart`.

3. Apply the new processing-state migration:

   ```bash
   php artisan migrate --force
   ```

4. Ensure the private storage root has enough free disk space for the source and temporary MP4 output during transcoding. The original is deleted only after the processed output is saved and the database points to it.
5. Back up the private storage directory and database together. Do not expose `storage/app/private` through a public symlink or web-server alias.

## Upload behaviour

- The server calculates chunk size from the configured chunk limit (`LEARNING_CHUNK_MB`, default 1 MB) and the PHP `upload_max_filesize` / `post_max_size` limits, reserving room for multipart overhead.
- The web server's request-body limit is outside PHP's control. If Nginx returns 413 before Laravel runs, raise `client_max_body_size` above the configured chunk size plus multipart overhead.
- Browser upload IDs are stored locally per selected file. Selecting the same file again resumes from chunks already received by the server.
- Interrupted uploads remain resumable for two hours; a resume request extends the session by another two hours.
- The final assembled file is size-checked, then moved into the private course-video area.
- A queued FFmpeg job creates an H.264/AAC MP4 with fast-start metadata. Only after successful processing is a local recording marked `active` / ready to play.
- If FFmpeg or processing fails, the recording is marked `failed`, the error is stored, and the original remains private for diagnosis or re-upload.
- A supplied external SpotPlayer ID remains in `waiting_contract` after local processing until the provider integration is ready.

## Access control

Local recordings are served only through the authenticated student stream route. Every stream request rechecks the student's active course enrollment and, for class-scoped recordings, active classroom membership. The file path itself remains private and responses use `private, no-store` cache headers.

For immediate revocation during long playback sessions, the current file response rechecks access on each HTTP/range request. A segmented HLS delivery layer with short-lived segment authorization is the next step if the business requires interruption in the middle of an already-open response.
