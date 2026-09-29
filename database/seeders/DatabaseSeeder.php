<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AccessKey;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
        ]);

        // Sellers
        $sellerA = User::factory()->create([
            'name' => 'Seller A',
            'email' => 'seller-a@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Seller,
        ]);

        $sellerB = User::factory()->create([
            'name' => 'Seller B',
            'email' => 'seller-b@example.com',
            'password' => Hash::make('password'),
            'role' => UserRole::Seller,
        ]);

        // Servers (no real Outline API needed for seed)
        $serverA = Server::create([
            'name' => 'SG Server',
            'api_url' => 'https://sg.example.com:8080',
            'cert_sha256' => null,
        ]);

        $serverB = Server::create([
            'name' => 'US Server',
            'api_url' => 'https://us.example.com:8080',
            'cert_sha256' => null,
        ]);

        // Real Outline server provided by user
        $realServer = Server::create([
            'name' => 'Production Server (200.141.5.144)',
            'api_url' => 'https://200.141.5.144:30143/gy6mZ1KpB24e3DgazVgTKg',
            'cert_sha256' => 'DBE8C8F1B1A8F42687240348598D9AEA88E048DAA05FFB610B7BCDF740A2F',
        ]);

        // Assign servers to sellers
        $sellerA->servers()->attach([$serverA->id, $realServer->id]);
        $sellerB->servers()->attach([$serverA->id, $serverB->id, $realServer->id]);

        // Sample access keys (outline_key_id is a placeholder; no real API call)
        AccessKey::create([
            'server_id' => $serverA->id,
            'created_by' => $sellerA->id,
            'outline_key_id' => 'sample-key-1',
            'name' => 'Alice VPN',
            'access_url' => 'ss://Y2hhY2hhMjAtaWV0Zi1wb2x5MTMwNTphbGljZXNlY3JldA==@sg.example.com:8080/?outline=1',
            'data_limit_bytes' => 10 * 1024 * 1024 * 1024, // 10 GB
            'expires_at' => now()->addDays(30),
        ]);

        AccessKey::create([
            'server_id' => $serverA->id,
            'created_by' => $sellerA->id,
            'outline_key_id' => 'sample-key-2',
            'name' => 'Bob VPN (expired)',
            'access_url' => 'ss://Y2hhY2hhMjAtaWV0Zi1wb2x5MTMwNTpib2JzZWNyZXQ=@sg.example.com:8080/?outline=1',
            'data_limit_bytes' => null,
            'expires_at' => now()->subDay(), // already expired
        ]);

        AccessKey::create([
            'server_id' => $serverB->id,
            'created_by' => $sellerB->id,
            'outline_key_id' => 'sample-key-3',
            'name' => 'Charlie VPN',
            'access_url' => 'ss://Y2hhY2hhMjAtaWV0Zi1wb2x5MTMwNTpjaGFybGllc2VjcmV0@us.example.com:8080/?outline=1',
            'data_limit_bytes' => 5 * 1024 * 1024 * 1024, // 5 GB
            'expires_at' => now()->addDays(2), // expiring soon
        ]);
    }
}
