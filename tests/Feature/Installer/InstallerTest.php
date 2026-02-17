<?php

use App\Models\User;

it('redirects to /install when app is not installed', function () {
    $installedPath = storage_path('app/installed');
    $wasInstalled = file_exists($installedPath);

    if ($wasInstalled) {
        unlink($installedPath);
    }

    $this->get('/')
        ->assertRedirect('/install');

    // Restore installed flag
    if ($wasInstalled) {
        file_put_contents($installedPath, now()->toIso8601String());
    }
});

it('redirects /install to /admin when app is already installed', function () {
    // Ensure installed flag exists
    if (! file_exists(storage_path('app/installed'))) {
        file_put_contents(storage_path('app/installed'), now()->toIso8601String());
    }

    $this->get('/install')
        ->assertRedirect('/admin');
});

it('can render requirements page when not installed', function () {
    $installedPath = storage_path('app/installed');
    $wasInstalled = file_exists($installedPath);

    if ($wasInstalled) {
        unlink($installedPath);
    }

    $this->get('/install/requirements')
        ->assertSuccessful()
        ->assertSee('Server Requirements');

    if ($wasInstalled) {
        file_put_contents($installedPath, now()->toIso8601String());
    }
});

it('can render database configuration page when not installed', function () {
    $installedPath = storage_path('app/installed');
    $wasInstalled = file_exists($installedPath);

    if ($wasInstalled) {
        unlink($installedPath);
    }

    $this->get('/install/database')
        ->assertSuccessful()
        ->assertSee('Database');

    if ($wasInstalled) {
        file_put_contents($installedPath, now()->toIso8601String());
    }
});

it('can render application settings page when not installed', function () {
    $installedPath = storage_path('app/installed');
    $wasInstalled = file_exists($installedPath);

    if ($wasInstalled) {
        unlink($installedPath);
    }

    $this->get('/install/application')
        ->assertSuccessful()
        ->assertSee('Application');

    if ($wasInstalled) {
        file_put_contents($installedPath, now()->toIso8601String());
    }
});

it('can render mail configuration page when not installed', function () {
    $installedPath = storage_path('app/installed');
    $wasInstalled = file_exists($installedPath);

    if ($wasInstalled) {
        unlink($installedPath);
    }

    $this->get('/install/mail')
        ->assertSuccessful()
        ->assertSee('Mail');

    if ($wasInstalled) {
        file_put_contents($installedPath, now()->toIso8601String());
    }
});

it('can render admin creation page when not installed', function () {
    $installedPath = storage_path('app/installed');
    $wasInstalled = file_exists($installedPath);

    if ($wasInstalled) {
        unlink($installedPath);
    }

    $this->get('/install/admin')
        ->assertSuccessful()
        ->assertSee('Admin');

    if ($wasInstalled) {
        file_put_contents($installedPath, now()->toIso8601String());
    }
});

it('validates admin creation form', function () {
    $installedPath = storage_path('app/installed');
    $wasInstalled = file_exists($installedPath);

    if ($wasInstalled) {
        unlink($installedPath);
    }

    $this->post('/install/admin', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ])
        ->assertSessionHasErrors(['name', 'email', 'password']);

    if ($wasInstalled) {
        file_put_contents($installedPath, now()->toIso8601String());
    }
});

it('creates admin user during installation', function () {
    $installedPath = storage_path('app/installed');
    $wasInstalled = file_exists($installedPath);

    if ($wasInstalled) {
        unlink($installedPath);
    }

    $this->post('/install/admin', [
        'name' => 'Test Admin',
        'email' => 'admin@installer-test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'company_name' => 'Test Company',
    ])
        ->assertRedirect(route('install.finalize'));

    $admin = User::where('email', 'admin@installer-test.com')->first();
    expect($admin)->not->toBeNull();
    expect($admin->name)->toBe('Test Admin');
    expect($admin->role)->toBe('admin');
    expect($admin->status)->toBe('active');
    expect($admin->company_name)->toBe('Test Company');

    if ($wasInstalled) {
        file_put_contents($installedPath, now()->toIso8601String());
    }
});

it('writes installed flag on finalization', function () {
    $installedPath = storage_path('app/installed');
    $wasInstalled = file_exists($installedPath);

    if ($wasInstalled) {
        unlink($installedPath);
    }

    $this->post('/install/finalize')
        ->assertRedirect('/admin');

    expect(file_exists($installedPath))->toBeTrue();

    // Cleanup is handled — flag is now written
});
