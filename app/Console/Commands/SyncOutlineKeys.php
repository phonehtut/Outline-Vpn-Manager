<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\OutlineKeySyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncOutlineKeys extends Command
{
    protected $signature = 'outline:sync-keys';

    protected $description = 'Sync access keys from all Outline servers into the database.';

    /**
     * Execute the console command.
     */
    public function handle(OutlineKeySyncService $keySync): int
    {
        $servers = Server::query()->orderBy('id')->get();
        $failed = 0;

        foreach ($servers as $server) {
            try {
                $counts = $keySync->sync($server);
                $this->info("Synced [{$server->name}]: imported {$counts['imported']}, updated {$counts['updated']}, removed {$counts['removed']}.");
            } catch (Throwable $exception) {
                $failed++;
                report($exception);
                $this->error("Failed to sync [{$server->name}]: {$exception->getMessage()}");
            }
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
