<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestSmtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'SMTP Connection Test - '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function buildHtml(): string
    {
        $appName = config('app.name', 'Maimaar');
        $timestamp = now()->format('M d, Y h:i A');

        return <<<HTML
        <div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h2 style="color: #1e3a5f;">SMTP Connection Test Successful</h2>
            <p>This is a test email sent from <strong>{$appName}</strong> to verify the SMTP configuration is working correctly.</p>
            <p style="color: #666; font-size: 14px;">Sent at: {$timestamp}</p>
            <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
            <p style="color: #999; font-size: 12px;">If you received this email, your mail settings are configured correctly.</p>
        </div>
        HTML;
    }
}
