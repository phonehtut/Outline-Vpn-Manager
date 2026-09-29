<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Seller/Dashboard', [
            'servers' => $user->servers()
                ->withCount('accessKeys')
                ->orderBy('name')
                ->get(['servers.id', 'servers.name']),
            'keyStats' => [
                'total' => $user->accessKeys()->count(),
                'active' => $user->accessKeys()
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->count(),
                'expired' => $user->accessKeys()
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now())
                    ->count(),
            ],
        ]);
    }
}
