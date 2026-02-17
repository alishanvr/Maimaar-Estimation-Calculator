@extends('installer.layout')

@section('content')
    <div class="text-center">
        <div class="flex justify-center mb-6">
            <div class="bg-brand-100 rounded-full p-4">
                <svg class="w-12 h-12 text-brand-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
            </div>
        </div>

        <h1 class="text-3xl font-bold text-gray-900 mb-4">Welcome to the Installation Wizard</h1>

        <p class="text-gray-600 text-lg mb-8 max-w-md mx-auto">
            This wizard will guide you through setting up your application. You'll configure database, application settings, email, and create your admin account.
        </p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-10 text-left max-w-md mx-auto">
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex-shrink-0">
                    <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-sm text-gray-600">Server requirements check</span>
            </div>
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex-shrink-0">
                    <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-sm text-gray-600">Database configuration</span>
            </div>
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex-shrink-0">
                    <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-sm text-gray-600">Application settings</span>
            </div>
            <div class="flex items-start gap-3">
                <div class="mt-0.5 flex-shrink-0">
                    <svg class="w-5 h-5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-sm text-gray-600">Admin account creation</span>
            </div>
        </div>

        <a href="{{ route('install.requirements') }}"
           class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 px-6 rounded-lg transition-colors duration-150">
            Start Installation
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
            </svg>
        </a>
    </div>
@endsection
