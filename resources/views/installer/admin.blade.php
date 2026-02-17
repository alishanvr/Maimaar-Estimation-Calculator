@extends('installer.layout')

@section('content')
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Create Admin Account</h2>
    <p class="text-sm text-gray-500 mb-6">Create the first administrator account. You'll use these credentials to log into the admin panel.</p>

    <form method="POST" action="{{ route('install.admin') }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="text-sm font-medium text-gray-700">Full Name</label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name') }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
                required
            >
        </div>

        <div>
            <label for="email" class="text-sm font-medium text-gray-700">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="{{ old('email') }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
                required
            >
        </div>

        <div>
            <label for="password" class="text-sm font-medium text-gray-700">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
                required
                minlength="8"
            >
        </div>

        <div>
            <label for="password_confirmation" class="text-sm font-medium text-gray-700">Confirm Password</label>
            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
                required
            >
        </div>

        <div>
            <label for="company_name" class="text-sm font-medium text-gray-700">
                Company Name
                <span class="text-gray-400 font-normal">(optional)</span>
            </label>
            <input
                type="text"
                id="company_name"
                name="company_name"
                value="{{ old('company_name') }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
            >
        </div>

        <div class="flex items-center justify-between pt-4">
            <a href="{{ route('install.mail') }}" class="text-gray-500 hover:text-gray-700 text-sm">&larr; Back</a>
            <button
                type="submit"
                class="bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 px-6 rounded-lg transition"
            >
                Create Admin &amp; Continue
            </button>
        </div>
    </form>
@endsection
