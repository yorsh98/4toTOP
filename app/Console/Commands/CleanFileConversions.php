<?php

namespace App\Console\Commands;

use App\Models\FileConversion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanFileConversions extends Command
{
    protected $signature = 'conversions:clean';

    protected $description = 'Remove expired conversion files while retaining activity records';

    public function handle(): int
    {
        FileConversion::query()
            ->whereNull('files_deleted_at')
            ->where(function ($query) {
                $query->where('created_at', '<', now()->subHours(config('conversions.download_hours') + 1))
                    ->orWhere('expires_at', '<', now());
            })
            ->whereIn('status', ['ready', 'failed'])
            ->chunkById(100, function ($jobs) {
                foreach ($jobs as $job) {
                    if (Storage::disk(config('conversions.disk'))->deleteDirectory('conversions/'.$job->token)) {
                        $job->update(['files_deleted_at' => now()]);
                    }
                }
            });

        return self::SUCCESS;
    }
}
