<?php

use App\Enums\UserRole;
use App\Models\User;

test('login page returns a successful response', function () {
    $response = $this->withoutVite()->get(route('login'));

    $response->assertOk();
});

test('authenticated user visiting login is redirected away', function () {
    $user = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($user)->get(route('login'))->assertRedirect();
});
