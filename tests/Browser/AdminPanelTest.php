<?php

use App\Models\User;

it('can access the admin dashboard', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    $page = visit('/admin');

    $page->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});

it('can access the system management page and see all sections', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    $page = visit('/admin/system-management');

    $page->assertSee('System Management')
        ->assertSee('Storage Usage Analysis')
        ->assertSee('Log File Management')
        ->assertNoJavascriptErrors();
});

it('can access the environment settings page', function () {
    $admin = User::factory()->superAdmin()->create();

    $this->actingAs($admin);

    $page = visit('/admin/environment-settings');

    $page->assertSee('Environment Settings')
        ->assertNoJavascriptErrors();
});
