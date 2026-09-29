<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\AccessKey;
use App\Models\Server;
use App\Services\OutlineApiService;
use App\Services\OutlineKeySyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AccessKeyController extends Controller
{
    public function __construct(
        private readonly OutlineApiService $outlineApi,
        private readonly OutlineKeySyncService $keySync,
    ) {}

    /**
     * Sync keys from the Outline server into the database.
     * Keys existing on server but not in DB are imported.
     * Existing keys have their name and data limit updated.
     * DB records whose outline_key_id no longer exists on the server are removed.
     */
    public function sync(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('viewAny', [AccessKey::class, $server]);

        try {
            $this->keySync->sync($server);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to connect to Outline server: '.$e->getMessage());
        }

        return back()->with('success', 'Keys synced from Outline server.');
    }

    public function index(Request $request, Server $server): Response
    {
        $this->authorize('viewAny', [AccessKey::class, $server]);

        $this->keySync->enforceExpiry($server);

        $keys = $server->accessKeys()
            ->with('creator:id,name')
            ->where('created_by', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Seller/Servers/Show', [
            'server' => $server->only('id', 'name'),
            'keys' => $keys,
        ]);
    }

    public function store(Request $request, Server $server): RedirectResponse
    {
        $this->authorize('create', [AccessKey::class, $server]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'data_limit_bytes' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        try {
            $outlineKey = $this->outlineApi->createKey(
                $server,
                $validated['name'],
                $validated['data_limit_bytes'] ?? null
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to create key on Outline server: '.$e->getMessage());
        }

        AccessKey::create([
            'server_id' => $server->id,
            'created_by' => $request->user()->id,
            'outline_key_id' => (string) $outlineKey['id'],
            'name' => $validated['name'],
            'access_url' => $outlineKey['accessUrl'] ?? null,
            'data_limit_bytes' => $validated['data_limit_bytes'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return back()->with('success', 'Access key created.');
    }

    public function update(Request $request, Server $server, AccessKey $accessKey): RedirectResponse
    {
        $this->authorize('update', $accessKey);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'data_limit_bytes' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $isExpired = ! empty($validated['expires_at']) && Carbon::parse($validated['expires_at'])->isPast();
        $effectiveLimit = $isExpired ? 0 : ($validated['data_limit_bytes'] ?? null);

        try {
            $this->outlineApi->renameKey($server, $accessKey->outline_key_id, $validated['name']);
            $this->outlineApi->setDataLimit($server, $accessKey->outline_key_id, $effectiveLimit);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to update key on Outline server: '.$e->getMessage());
        }

        $accessKey->update([
            'name' => $validated['name'],
            'data_limit_bytes' => $validated['data_limit_bytes'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return back()->with('success', 'Access key updated.');
    }

    public function destroy(Request $request, Server $server, AccessKey $accessKey): RedirectResponse
    {
        $this->authorize('delete', $accessKey);

        try {
            $this->outlineApi->deleteKey($server, $accessKey);
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to delete key from Outline server: '.$e->getMessage());
        }

        $accessKey->delete();

        return back()->with('success', 'Access key deleted.');
    }
}
