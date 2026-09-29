<?php

namespace App\Services;

use App\Models\AccessKey;
use App\Models\Server;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
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
     * Fetch per-key metrics for the trailing 30 days.
     *
     * @return array{supported: bool, keys: array<string, array{usage_bytes: int, last_active_at: int|null, peak_device_count: int|null, peak_device_at: int|null}>}|null
     */
    public function getAccessKeyMetrics(Server $server): ?array
    {
        try {
            $metrics = $this->client($server)
                ->get('/experimental/server/metrics', ['since' => '30d'])
                ->throw()
                ->json('accessKeys', []);

            $keys = [];

            foreach ($metrics as $metric) {
                $keyId = (string) ($metric['accessKeyId'] ?? '');

                if ($keyId === '') {
                    continue;
                }

                $keys[$keyId] = [
                    'usage_bytes' => (int) ($metric['dataTransferred']['bytes'] ?? 0),
                    'last_active_at' => isset($metric['connection']['lastTrafficSeen'])
                        ? (int) $metric['connection']['lastTrafficSeen']
                        : null,
                    'peak_device_count' => isset($metric['connection']['peakDeviceCount']['data'])
                        ? (int) $metric['connection']['peakDeviceCount']['data']
                        : null,
                    'peak_device_at' => isset($metric['connection']['peakDeviceCount']['timestamp'])
                        ? (int) $metric['connection']['peakDeviceCount']['timestamp']
                        : null,
                ];
            }

            return ['supported' => true, 'keys' => $keys];
        } catch (RequestException $exception) {
            if ($exception->response->status() !== 404) {
                return null;
            }
        } catch (ConnectionException) {
            return null;
        }

        try {
            $usage = $this->client($server)
                ->get('/metrics/transfer')
                ->throw()
                ->json('bytesTransferredByUserId', []);

            $keys = [];

            foreach ($usage as $keyId => $bytes) {
                $keys[(string) $keyId] = [
                    'usage_bytes' => (int) $bytes,
                    'last_active_at' => null,
                    'peak_device_count' => null,
                    'peak_device_at' => null,
                ];
            }

            return ['supported' => false, 'keys' => $keys];
        } catch (ConnectionException|RequestException) {
            return null;
        }
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
