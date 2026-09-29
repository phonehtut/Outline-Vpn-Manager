<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AccessKey;
use App\Models\Server;
use App\Models\User;
use RuntimeException;
use Throwable;

class OutlineKeySyncService
{
    public function __construct(private readonly OutlineApiService $outlineApi) {}

    /**
     * Sync remote keys into the local database and enforce expired-key limits.
     *
     * @return array{imported: int, updated: int, removed: int}
     */
    public function sync(Server $server): array
    {
        $remoteKeys = $this->outlineApi->listKeys($server);
        $remoteIds = collect($remoteKeys)->pluck('id')->map(fn ($id) => (string) $id)->all();

        $removed = $server->accessKeys()
            ->whereNotIn('outline_key_id', $remoteIds)
            ->delete();

        $existingKeys = $server->accessKeys()->get()->keyBy('outline_key_id');
        $admin = null;
        $imported = 0;
        $updated = 0;

        foreach ($remoteKeys as $remoteKey) {
            $remoteId = (string) $remoteKey['id'];
            $attributes = [
                'name' => ! empty($remoteKey['name']) ? $remoteKey['name'] : "Key #{$remoteId}",
                'access_url' => $remoteKey['accessUrl'] ?? null,
                'data_limit_bytes' => $remoteKey['dataLimit']['bytes'] ?? null,
            ];

            if ($existingKeys->has($remoteId)) {
                $existingKeys->get($remoteId)->update($attributes);
                $updated++;

                continue;
            }

            $admin ??= User::query()
                ->where('role', UserRole::Admin)
                ->orderBy('id')
                ->first();

            if ($admin === null) {
                throw new RuntimeException('Cannot import Outline keys because no admin account exists.');
            }

            AccessKey::create([
                ...$attributes,
                'server_id' => $server->id,
                'created_by' => $admin->id,
                'outline_key_id' => $remoteId,
                'expires_at' => null,
            ]);
            $imported++;
        }

        $this->enforceExpiry($server);

        return ['imported' => $imported, 'updated' => $updated, 'removed' => $removed];
    }

    public function enforceExpiry(Server $server): void
    {
        $expiredKeys = $server->accessKeys()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($expiredKeys as $key) {
            try {
                $this->outlineApi->setDataLimit($server, $key->outline_key_id, 0);
            } catch (Throwable) {
                // Keep sync available when expiry enforcement cannot reach the server.
            }
        }
    }
}
