@extends('installer.layout')

@section('content')
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Mail Configuration</h2>
    <p class="text-sm text-gray-500 mb-6">Configure how the application sends emails. You can change these settings later in the admin panel.</p>

    <form method="POST" action="{{ route('install.mail') }}" class="space-y-5">
        @csrf

        <div>
            <label for="mail_mailer" class="text-sm font-medium text-gray-700">Mail Driver</label>
            <select
                id="mail_mailer"
                name="mail_mailer"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
            >
                <option value="smtp" {{ old('mail_mailer') === 'smtp' ? 'selected' : '' }}>smtp</option>
                <option value="log" {{ old('mail_mailer') === 'log' ? 'selected' : '' }}>log</option>
                <option value="sendmail" {{ old('mail_mailer') === 'sendmail' ? 'selected' : '' }}>sendmail</option>
                <option value="array" {{ old('mail_mailer') === 'array' ? 'selected' : '' }}>array</option>
            </select>
        </div>

        <div>
            <label for="mail_host" class="text-sm font-medium text-gray-700">Mail Host</label>
            <input
                type="text"
                id="mail_host"
                name="mail_host"
                value="{{ old('mail_host') }}"
                placeholder="smtp.gmail.com"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
            >
        </div>

        <div>
            <label for="mail_port" class="text-sm font-medium text-gray-700">Mail Port</label>
            <input
                type="text"
                id="mail_port"
                name="mail_port"
                value="{{ old('mail_port') }}"
                placeholder="587"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
            >
        </div>

        <div>
            <label for="mail_username" class="text-sm font-medium text-gray-700">Mail Username</label>
            <input
                type="text"
                id="mail_username"
                name="mail_username"
                value="{{ old('mail_username') }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
            >
        </div>

        <div>
            <label for="mail_password" class="text-sm font-medium text-gray-700">Mail Password</label>
            <input
                type="password"
                id="mail_password"
                name="mail_password"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
            >
        </div>

        <div>
            <label for="mail_scheme" class="text-sm font-medium text-gray-700">Encryption</label>
            <select
                id="mail_scheme"
                name="mail_scheme"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
            >
                <option value="" {{ old('mail_scheme') === '' ? 'selected' : '' }}>None</option>
                <option value="tls" {{ old('mail_scheme') === 'tls' ? 'selected' : '' }}>tls</option>
                <option value="ssl" {{ old('mail_scheme') === 'ssl' ? 'selected' : '' }}>ssl</option>
            </select>
        </div>

        <div>
            <label for="mail_from_address" class="text-sm font-medium text-gray-700">From Address</label>
            <input
                type="email"
                id="mail_from_address"
                name="mail_from_address"
                value="{{ old('mail_from_address') }}"
                placeholder="noreply@your-domain.com"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
            >
        </div>

        <div>
            <label for="mail_from_name" class="text-sm font-medium text-gray-700">From Name</label>
            <input
                type="text"
                id="mail_from_name"
                name="mail_from_name"
                value="{{ old('mail_from_name') }}"
                placeholder="Maimaar"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
            >
        </div>

        <div class="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-700">
            <strong>Tip:</strong> Use 'log' driver for development &mdash; emails will be written to the log file instead of being sent.
        </div>

        <div class="flex items-center justify-between pt-4">
            <a href="{{ route('install.application') }}" class="text-gray-500 hover:text-gray-700 text-sm">&larr; Back</a>
            <div class="flex items-center gap-4">
                <a href="{{ route('install.admin') }}" class="text-gray-500 hover:text-gray-700 text-sm">Skip</a>
                <button
                    type="submit"
                    class="bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 px-6 rounded-lg transition"
                >
                    Save &amp; Continue
                </button>
            </div>
        </div>
    </form>
@endsection
