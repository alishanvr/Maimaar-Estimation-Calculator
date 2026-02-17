@extends('installer.layout')

@section('content')
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Database Migrations</h2>
    <p class="text-gray-600 mb-6">Run database migrations to create the required tables.</p>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-800">What happens next?</p>
                <p class="text-sm text-blue-700 mt-0.5">
                    Migrations will create all necessary database tables. This process may take a moment depending on your server speed.
                </p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('install.migrations') }}" class="space-y-5">
        @csrf

        <div class="flex items-start gap-3 p-4 rounded-lg border border-gray-200 bg-gray-50">
            <input type="checkbox"
                   name="seed_reference_data"
                   id="seed_reference_data"
                   value="1"
                   checked
                   class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-600 focus:ring-brand-500">
            <label for="seed_reference_data" class="flex-1 cursor-pointer">
                <span class="text-sm font-medium text-gray-700 block">Seed reference data</span>
                <span class="text-sm text-gray-500">Recommended for first install. Populates the database with default settings and reference data required for the application to function correctly.</span>
            </label>
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('install.database') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium transition-colors duration-150">
                &larr; Back
            </a>

            <button type="submit"
                    class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 px-6 rounded-lg transition-colors duration-150">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                </svg>
                Run Migrations
            </button>
        </div>
    </form>
@endsection
