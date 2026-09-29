<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessKey;
use App\Models\Server;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'seller_count' => User::where('role', 'seller')->count(),
                'server_count' => Server::count(),
                'key_count' => AccessKey::count(),
                'expired_key_count' => AccessKey::whereNotNull('expires_at')
                    ->where('expires_at', '<=', now())
                    ->count(),
            ],
        ]);
    }
}
