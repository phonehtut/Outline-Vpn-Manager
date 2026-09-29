<?php

use App\Enums\UserRole;
use App\Models\AccessKey;
use App\Models\Server;
use App\Models\User;
use App\Services\OutlineApiService;

beforeEach(function () {
    $this->seller = User::factory()->create(['role' => UserRole::Seller]);
    $this->server = Server::factory()->create();
    $this->seller->servers()->attach($this->server);

    // Mock the Outline API so tests don't hit real servers
    $this->outlineApi = Mockery::mock(OutlineApiService::class);
    $this->app->instance(OutlineApiService::class, $this->outlineApi);
});

test('seller can view keys on their assigned server', function () {
    AccessKey::factory()->for($this->server)->create([
        'created_by' => $this->seller->id,
    ]);

    $this->actingAs($this->seller)
        ->withoutVite()
        ->get(route('seller.servers.keys.index', $this->server))
        ->assertOk();
});

test('seller cannot view keys on a server they are not assigned to', function () {
    $otherServer = Server::factory()->create();

    $this->actingAs($this->seller)
        ->get(route('seller.servers.keys.index', $otherServer))
        ->assertForbidden();
});

test('seller can create a key on their assigned server', function () {
    $this->outlineApi
        ->shouldReceive('createKey')
        ->once()
        ->andReturn(['id' => 'new-key-1', 'name' => 'Test Key']);

    $this->actingAs($this->seller)
        ->post(route('seller.servers.keys.store', $this->server), [
            'name' => 'Test Key',
            'expires_at' => now()->addDays(30)->toDateString(),
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('access_keys', [
        'server_id' => $this->server->id,
        'created_by' => $this->seller->id,
        'name' => 'Test Key',
        'outline_key_id' => 'new-key-1',
    ]);
});

test('seller can delete their own key', function () {
    $key = AccessKey::factory()->for($this->server)->create([
        'created_by' => $this->seller->id,
        'outline_key_id' => 'key-to-delete',
    ]);

    $this->outlineApi
        ->shouldReceive('deleteKey')
        ->once();

    $this->actingAs($this->seller)
        ->delete(route('seller.servers.keys.destroy', [$this->server, $key]))
        ->assertRedirect();

    $this->assertModelMissing($key);
});

test('seller cannot delete another seller\'s key', function () {
    $otherSeller = User::factory()->create(['role' => UserRole::Seller]);
    $otherSeller->servers()->attach($this->server);

    $key = AccessKey::factory()->for($this->server)->create([
        'created_by' => $otherSeller->id,
    ]);

    $this->actingAs($this->seller)
        ->delete(route('seller.servers.keys.destroy', [$this->server, $key]))
        ->assertForbidden();
});

test('seller can sync keys from outline server', function () {
    $staleKey = AccessKey::factory()->for($this->server)->create([
        'created_by' => $this->seller->id,
        'outline_key_id' => 'stale-key',
    ]);

    $existingKey = AccessKey::factory()->for($this->server)->create([
        'created_by' => $this->seller->id,
        'outline_key_id' => 'existing-key',
        'name' => 'Old Name',
    ]);

    $this->outlineApi
        ->shouldReceive('listKeys')
        ->once()
        ->andReturn([
            ['id' => 'existing-key', 'name' => 'Renamed On Server', 'dataLimit' => ['bytes' => 5000]],
            ['id' => 'brand-new-key', 'name' => 'Imported Key'],
        ]);

    $this->actingAs($this->seller)
        ->post(route('seller.servers.keys.sync', $this->server))
        ->assertRedirect();

    $this->assertModelMissing($staleKey);
    expect($existingKey->fresh()->name)->toBe('Renamed On Server');
    expect($existingKey->fresh()->data_limit_bytes)->toBe(5000);

    $this->assertDatabaseHas('access_keys', [
        'server_id' => $this->server->id,
        'outline_key_id' => 'brand-new-key',
        'name' => 'Imported Key',
    ]);
});

test('viewing server keys automatically blocks expired keys with 0 byte data limit on outline server', function () {
    $expiredKey = AccessKey::factory()->for($this->server)->create([
        'created_by' => $this->seller->id,
        'outline_key_id' => 'expired-key-1',
        'expires_at' => now()->subDay(),
    ]);

    $this->outlineApi
        ->shouldReceive('setDataLimit')
        ->with(Mockery::any(), 'expired-key-1', 0)
        ->once();

    $this->actingAs($this->seller)
        ->withoutVite()
        ->get(route('seller.servers.keys.index', $this->server))
        ->assertOk();
});
