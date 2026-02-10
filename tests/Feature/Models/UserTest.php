<?php

use App\Models\User;

it('has admin role check', function () {
    $admin = User::factory()->admin()->make();
    $user = User::factory()->make();

    expect($admin->isAdmin())->toBeTrue();
    expect($user->isAdmin())->toBeFalse();
});

it('has active status check', function () {
    $active = User::factory()->make();
    $revoked = User::factory()->revoked()->make();

    expect($active->isActive())->toBeTrue();
    expect($revoked->isActive())->toBeFalse();
});

it('has estimations relationship', function () {
    expect((new User)->estimations())->toBeInstanceOf(
        \Illuminate\Database\Eloquent\Relations\HasMany::class
    );
});

it('hides password and remember_token', function () {
    $user = User::factory()->make();
    $array = $user->toArray();

    expect($array)->not->toHaveKey('password');
    expect($array)->not->toHaveKey('remember_token');
});
