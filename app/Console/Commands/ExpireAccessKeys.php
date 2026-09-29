<?php

namespace App\Console\Commands;

use App\Models\AccessKey;
use App\Services\OutlineApiService;
use Illuminate\Console\Command;
use Throwable;

class ExpireAccessKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keys:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete expired access keys from Outline servers and the database.';

    public function handle(OutlineApiService $outlineApi): int
    {
        $expiredKeys = AccessKey::with('server')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expiredKeys->isEmpty()) {
            $this->info('No expired keys found.');

            return self::SUCCESS;
        }

        $deleted = 0;
        $failed = 0;

        foreach ($expiredKeys as $key) {
            try {
                $outlineApi->deleteKey($key->server, $key);
                $key->delete();
                $deleted++;
                $this->line("  Deleted key [{$key->name}] from server [{$key->server->name}]");
            } catch (Throwable $e) {
                $failed++;
                $this->warn("  Failed to delete key [{$key->name}]: {$e->getMessage()}");
            }
        }

        $this->info("Done. Deleted: {$deleted}, Failed: {$failed}.");

        return self::SUCCESS;
    }
}
