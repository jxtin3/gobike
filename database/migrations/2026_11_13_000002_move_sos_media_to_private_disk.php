<?php

use App\Support\Media;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/*
 * SOS photos and videos used to be saved on the public media disk, where anyone with the link
 * could open them. They now live on the private disk and only admins open them (see
 * SosRequest::mediaDisk). This copies the existing files over and removes the public copies.
 * The paths stay the same ("sos/…"). A file that cannot be copied is left where it is and logged;
 * the admin page still finds it there.
 */
return new class extends Migration
{
    public function up(): void
    {
        $fromName = Media::disk();
        $toName = (string) config('filesystems.private_disk');
        if ($fromName === $toName) {
            return;
        }

        $from = Storage::disk($fromName);
        $to = Storage::disk($toName);

        DB::table('sos_requests')
            ->where(fn ($q) => $q->whereNotNull('image_path')->orWhereNotNull('video_path'))
            ->orderBy('id')
            ->select(['id', 'image_path', 'video_path'])
            ->chunk(100, function ($rows) use ($from, $to) {
                foreach ($rows as $row) {
                    foreach ([$row->image_path, $row->video_path] as $path) {
                        if (! $path || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                            continue;
                        }

                        try {
                            if (! $to->exists($path)) {
                                if (! $from->exists($path)) {
                                    continue;
                                }
                                $stream = $from->readStream($path);
                                $to->writeStream($path, $stream);
                                if (is_resource($stream)) {
                                    fclose($stream);
                                }
                            }

                            if ($to->exists($path)) {
                                $from->delete($path);
                            }
                        } catch (Throwable $e) {
                            Log::warning("Could not move SOS media {$path} to the private disk: {$e->getMessage()}");
                        }
                    }
                }
            });
    }

    public function down(): void
    {
        // The files stay private; the admin page reads them from either disk.
    }
};
