<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\User;
use App\Services\OutlineApiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServerController extends Controller
{
    public function __construct(private readonly OutlineApiService $outlineApi) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Servers/Index', [
            'servers' => Server::withCount('accessKeys')
                ->with('sellers:id,name,email')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Servers/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->filled('config_json')) {
            $decoded = json_decode((string) $request->input('config_json'), true);
            if (is_array($decoded)) {
                $request->merge([
                    'api_url' => $request->input('api_url') ?: ($decoded['apiUrl'] ?? null),
                    'cert_sha256' => $request->input('cert_sha256') ?: ($decoded['certSha256'] ?? null),
                ]);
            }
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'api_url' => ['required', 'url'],
            'cert_sha256' => ['nullable', 'string', 'max:128'],
        ]);

        $name = $validated['name'] ?? null;
        if (empty($name)) {
            $info = $this->outlineApi->getServerInfo($validated['api_url']);
            $host = parse_url($validated['api_url'], PHP_URL_HOST) ?: 'Server';
            $name = ! empty($info['name']) ? $info['name'] : "Outline ({$host})";
        }

        Server::create([
            'name' => $name,
            'api_url' => $validated['api_url'],
            'cert_sha256' => $validated['cert_sha256'] ?? null,
        ]);

        return redirect()->route('admin.servers.index')
            ->with('success', 'Server added successfully.');
    }

    public function edit(Server $server): Response
    {
        $server->load('sellers:id,name,email');

        return Inertia::render('Admin/Servers/Edit', [
            'server' => $server,
            'sellers' => User::where('role', UserRole::Seller)
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }

    public function update(Request $request, Server $server): RedirectResponse
    {
        if ($request->filled('config_json')) {
            $decoded = json_decode((string) $request->input('config_json'), true);
            if (is_array($decoded)) {
                $request->merge([
                    'api_url' => $request->input('api_url') ?: ($decoded['apiUrl'] ?? null),
                    'cert_sha256' => $request->input('cert_sha256') ?: ($decoded['certSha256'] ?? null),
                ]);
            }
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'api_url' => ['required', 'url'],
            'cert_sha256' => ['nullable', 'string', 'max:128'],
            'seller_ids' => ['nullable', 'array'],
            'seller_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $server->update([
            'name' => $validated['name'],
            'api_url' => $validated['api_url'],
            'cert_sha256' => $validated['cert_sha256'] ?? null,
        ]);

        $server->sellers()->sync($validated['seller_ids'] ?? []);

        return redirect()->route('admin.servers.index')
            ->with('success', 'Server updated successfully.');
    }

    public function destroy(Server $server): RedirectResponse
    {
        $server->delete();

        return redirect()->route('admin.servers.index')
            ->with('success', 'Server deleted.');
    }
}
