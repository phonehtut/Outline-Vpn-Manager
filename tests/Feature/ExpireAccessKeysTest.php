<?php

use App\Enums\UserRole;
use App\Models\AccessKey;
use App\Models\Server;
use App\Models\User;
use App\Services\OutlineApiService;

test('expired keys are deleted from server and database', function () {
    $server = Server::factory()->create();
    $seller = User::factory()->create(['role' => UserRole::Seller]);

    $expiredKey = AccessKey::factory()->for($server)->create([
        'created_by' => $seller->id,
        'expires_at' => now()->subHour(),
    ]);

    $activeKey = AccessKey::factory()->for($server)->create([
        'created_by' => $seller->id,
        'expires_at' => now()->addDays(10),
    ]);

    $noExpiryKey = AccessKey::factory()->for($server)->create([
        'created_by' => $seller->id,
        'expires_at' => null,
    ]);

    $mock = Mockery::mock(OutlineApiService::class);
    $mock->shouldReceive('deleteKey')->once()->with(
        Mockery::on(fn ($s) => $s->id === $server->id),
        Mockery::on(fn ($k) => $k->id === $expiredKey->id),
    );
    app()->instance(OutlineApiService::class, $mock);

    $this->artisan('keys:expire')->assertSuccessful();

    $this->assertModelMissing($expiredKey);
    $this->assertModelExists($activeKey);
    $this->assertModelExists($noExpiryKey);
});

test('command outputs success when no expired keys found', function () {
    $this->artisan('keys:expire')
        ->expectsOutput('No expired keys found.')
        ->assertSuccessful();
});
