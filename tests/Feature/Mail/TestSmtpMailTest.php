<?php

use App\Mail\TestSmtpMail;

it('renders the test smtp email correctly', function () {
    $mailable = new TestSmtpMail;

    $mailable->assertHasSubject('SMTP Connection Test - '.config('app.name'));
});

it('contains expected content', function () {
    $mailable = new TestSmtpMail;

    $rendered = $mailable->render();

    expect($rendered)->toContain('SMTP Connection Test Successful');
    expect($rendered)->toContain('verify the SMTP configuration');
});
