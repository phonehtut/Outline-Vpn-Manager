<?php

use App\Enums\UserRole;
use App\Models\Server;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['role' => UserRole::Admin]);
});

test('admin can view sellers list', function () {
    User::factory()->count(3)->create(['role' => UserRole::Seller]);

    $this->actingAs($this->admin)
        ->withoutVite()
        ->get(route('admin.sellers.index'))
        ->assertOk();
});

test('admin can create a seller', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.sellers.store'), [
            'name' => 'New Seller',
            'email' => 'newseller@example.com',
            'password' => 'password123!',
            'password_confirmation' => 'password123!',
        ])
        ->assertRedirect(route('admin.sellers.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'newseller@example.com',
        'role' => UserRole::Seller->value,
    ]);
});

test('admin can update a seller and assign servers', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);
    $server = Server::factory()->create();

    $this->actingAs($this->admin)
        ->patch(route('admin.sellers.update', $seller), [
            'name' => 'Updated Name',
            'email' => $seller->email,
            'server_ids' => [$server->id],
        ])
        ->assertRedirect(route('admin.sellers.index'));

    expect($seller->fresh()->name)->toBe('Updated Name');
    expect($seller->servers()->where('servers.id', $server->id)->exists())->toBeTrue();
});

test('admin can delete a seller', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);

    $this->actingAs($this->admin)
        ->delete(route('admin.sellers.destroy', $seller))
        ->assertRedirect(route('admin.sellers.index'));

    $this->assertModelMissing($seller);
});
