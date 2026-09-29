<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class SellerController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Sellers/Index', [
            'sellers' => User::where('role', UserRole::Seller)
                ->with('servers:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Sellers/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => UserRole::Seller,
        ]);

        return redirect()->route('admin.sellers.index')
            ->with('success', 'Seller created successfully.');
    }

    public function edit(User $seller): Response
    {
        $seller->load('servers:id,name');

        return Inertia::render('Admin/Sellers/Edit', [
            'seller' => $seller->only('id', 'name', 'email', 'servers'),
            'servers' => Server::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $seller): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', "unique:users,email,{$seller->id}"],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'server_ids' => ['nullable', 'array'],
            'server_ids.*' => ['integer', 'exists:servers,id'],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = $validated['password'];
        }

        $seller->update($updateData);

        $seller->servers()->sync($validated['server_ids'] ?? []);

        return redirect()->route('admin.sellers.index')
            ->with('success', 'Seller updated successfully.');
    }

    public function destroy(User $seller): RedirectResponse
    {
        $seller->delete();

        return redirect()->route('admin.sellers.index')
            ->with('success', 'Seller deleted.');
    }
}
