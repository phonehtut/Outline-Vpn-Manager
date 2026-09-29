<?php

use App\Enums\UserRole;
use App\Models\User;

test('unauthenticated user is redirected to login', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->get(route('seller.dashboard'))->assertRedirect(route('login'));
});

test('seller cannot access admin routes', function () {
    $seller = User::factory()->create(['role' => UserRole::Seller]);

    $this->actingAs($seller)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin cannot access seller routes', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->get(route('seller.dashboard'))
        ->assertForbidden();
});

test('admin can log in and is redirected to admin dashboard', function () {
    $admin = User::factory()->create([
        'role' => UserRole::Admin,
        'password' => bcrypt('password'),
    ]);

    $this->withoutVite()
        ->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('admin.dashboard'));
});

test('seller can log in and is redirected to seller dashboard', function () {
    $seller = User::factory()->create([
        'role' => UserRole::Seller,
        'password' => bcrypt('password'),
    ]);

    $this->withoutVite()
        ->post(route('login.store'), [
            'email' => $seller->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('seller.dashboard'));
});

test('login fails with wrong credentials', function () {
    $this->withoutVite()
        ->post(route('login.store'), [
            'email' => 'nobody@example.com',
            'password' => 'wrong',
        ])
        ->assertSessionHasErrors('email');
});
