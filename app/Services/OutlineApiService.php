<?php

namespace App\Services;

use App\Models\AccessKey;
use App\Models\Server;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class OutlineApiService
{
    /**
     * Build a configured HTTP client for the given server.
     * Outline uses a self-signed cert, so we verify with the stored fingerprint or skip verification.
     */
    private function client(Server $server): PendingRequest
    {
        return Http::withoutVerifying()
            ->timeout(10)
            ->baseUrl(rtrim($server->api_url, '/'))
            ->acceptJson()
            ->contentType('application/json');
    }

    /**
     * Fetch server info (/server) from an Outline Management API URL.
     *
     * @return array<string, mixed>
     */
    public function getServerInfo(string $apiUrl): array
    {
        try {
            $response = Http::withoutVerifying()
                ->timeout(5)
                ->acceptJson()
                ->get(rtrim($apiUrl, '/').'/server');

            if ($response->successful()) {
                return $response->json() ?? [];
            }
        } catch (\Throwable) {
            // Fall back to default name if server is unreachable during creation
        }

        return [];
    }

    /**
     * List all access keys on the server.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws ConnectionException
     */
    public function listKeys(Server $server): array
    {
        $response = $this->client($server)->get('/access-keys')->throw();

        return $response->json('accessKeys', []);
    }

    /**
     * Create a new access key on the Outline server.
     *
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function createKey(Server $server, string $name, ?int $dataLimitBytes = null): array
    {
        $response = $this->client($server)->post('/access-keys', [
            'name' => $name,
        ])->throw();

        $key = $response->json();

        if (($key['name'] ?? null) !== $name) {
            $this->renameKey($server, (string) $key['id'], $name);
            $key['name'] = $name;
        }

        if ($dataLimitBytes !== null) {
            $this->setDataLimit($server, (string) $key['id'], $dataLimitBytes);
        }

        return $key;
    }

    /**
     * Rename an existing access key on the Outline server.
     *
     * @throws ConnectionException
     */
    public function renameKey(Server $server, string $outlineKeyId, string $name): void
    {
        $this->client($server)->put("/access-keys/{$outlineKeyId}/name", [
            'name' => $name,
        ])->throw();
    }

    /**
     * Update the data limit for an existing key.
     *
     * @throws ConnectionException
     */
    public function setDataLimit(Server $server, string $outlineKeyId, ?int $dataLimitBytes): void
    {
        if ($dataLimitBytes !== null) {
            $this->client($server)->put("/access-keys/{$outlineKeyId}/data-limit", [
                'limit' => ['bytes' => $dataLimitBytes],
            ])->throw();
        } else {
            $this->client($server)->delete("/access-keys/{$outlineKeyId}/data-limit")->throw();
        }
    }

    /**
     * Delete an access key from the Outline server.
     *
     * @throws ConnectionException
     */
    public function deleteKey(Server $server, AccessKey $accessKey): void
    {
        $this->client($server)->delete("/access-keys/{$accessKey->outline_key_id}")->throw();
    }
}
