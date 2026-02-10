<?php

use App\Models\Estimation;
use App\Models\User;

it('allows user to view own estimation', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    expect($user->can('view', $estimation))->toBeTrue();
});

it('denies user from viewing another users estimation', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $estimation = Estimation::factory()->create(['user_id' => $otherUser->id]);

    expect($user->can('view', $estimation))->toBeFalse();
});

it('allows admin to view any estimation', function () {
    $admin = User::factory()->admin()->create();
    $otherUser = User::factory()->create();
    $estimation = Estimation::factory()->create(['user_id' => $otherUser->id]);

    expect($admin->can('view', $estimation))->toBeTrue();
});

it('allows user to update own estimation', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    expect($user->can('update', $estimation))->toBeTrue();
});

it('denies user from updating another users estimation', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $estimation = Estimation::factory()->create(['user_id' => $otherUser->id]);

    expect($user->can('update', $estimation))->toBeFalse();
});

it('allows admin to delete any estimation', function () {
    $admin = User::factory()->admin()->create();
    $otherUser = User::factory()->create();
    $estimation = Estimation::factory()->create(['user_id' => $otherUser->id]);

    expect($admin->can('delete', $estimation))->toBeTrue();
});

it('allows any authenticated user to create estimations', function () {
    $user = User::factory()->create();

    expect($user->can('create', Estimation::class))->toBeTrue();
});

it('allows user to calculate own estimation', function () {
    $user = User::factory()->create();
    $estimation = Estimation::factory()->create(['user_id' => $user->id]);

    expect($user->can('calculate', $estimation))->toBeTrue();
});
