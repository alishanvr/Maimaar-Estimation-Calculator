<?php

use App\Models\User;

it('loads the login page without javascript errors', function () {
    $page = visit('/login');

    $page->assertNoJavascriptErrors();
});

it('loads authenticated frontend pages without javascript errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $pages = visit(['/', '/estimations', '/projects', '/reports', '/profile']);

    $pages->assertNoJavascriptErrors();
});
