<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessKey;
use App\Models\Server;
use App\Services\OutlineApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AccessKeyController extends Controller
{
    public function __construct(private readonly OutlineApiService $outlineApi) {}

    public function index(): Response
    {
        return Inertia::render('Admin/AccessKeys/Index', [
            'keys' => AccessKey::query()
                ->with(['creator:id,name,email', 'server:id,name'])
                ->orderByDesc('created_at')
                ->paginate(25)
                ->withQueryString(),
            'servers' => Server::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'server_id' => ['required', 'integer', 'exists:servers,id'],
            'name' => ['required', 'string', 'max:255'],
            'data_limit_bytes' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $server = Server::findOrFail($validated['server_id']);

        try {
            $outlineKey = $this->outlineApi->createKey(
                $server,
                $validated['name'],
                $validated['data_limit_bytes'] ?? null,
            );
        } catch (\Throwable $exception) {
            return back()->with('error', 'Failed to create key on Outline server: '.$exception->getMessage());
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

    public function update(Request $request, AccessKey $accessKey): RedirectResponse
    {
        $this->authorize('update', $accessKey);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'data_limit_bytes' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $server = $accessKey->server;
        $isExpired = ! empty($validated['expires_at']) && Carbon::parse($validated['expires_at'])->isPast();
        $effectiveLimit = $isExpired ? 0 : ($validated['data_limit_bytes'] ?? null);

        try {
            $this->outlineApi->renameKey($server, $accessKey->outline_key_id, $validated['name']);
            $this->outlineApi->setDataLimit($server, $accessKey->outline_key_id, $effectiveLimit);
        } catch (\Throwable $exception) {
            return back()->with('error', 'Failed to update key on Outline server: '.$exception->getMessage());
        }

        $accessKey->update([
            'name' => $validated['name'],
            'data_limit_bytes' => $validated['data_limit_bytes'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return back()->with('success', 'Access key updated.');
    }

    public function destroy(AccessKey $accessKey): RedirectResponse
    {
        $this->authorize('delete', $accessKey);

        try {
            $this->outlineApi->deleteKey($accessKey->server, $accessKey);
        } catch (\Throwable $exception) {
            return back()->with('error', 'Failed to delete key from Outline server: '.$exception->getMessage());
        }

        $accessKey->delete();

        return back()->with('success', 'Access key deleted.');
    }
}
