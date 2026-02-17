@extends('installer.layout')

@section('content')
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Finalize Installation</h2>
    <p class="text-sm text-gray-500 mb-6">Everything is configured! Click the button below to complete the installation.</p>

    <div class="mb-6 space-y-3">
        <div class="flex items-center gap-3 text-sm text-gray-700">
            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">&#10003;</span>
            Storage symlink will be created
        </div>
        <div class="flex items-center gap-3 text-sm text-gray-700">
            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">&#10003;</span>
            Application key will be verified
        </div>
        <div class="flex items-center gap-3 text-sm text-gray-700">
            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">&#10003;</span>
            Caches will be cleared
        </div>
        <div class="flex items-center gap-3 text-sm text-gray-700">
            <span class="flex-shrink-0 w-5 h-5 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-xs font-bold">&#10003;</span>
            Installation will be marked as complete
        </div>
    </div>

    <div class="rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700 mb-6">
        After installation, you'll be redirected to the admin login page.
    </div>

    <form method="POST" action="{{ route('install.finalize') }}">
        @csrf

        <div class="flex items-center justify-between">
            <a href="{{ route('install.admin') }}" class="text-gray-500 hover:text-gray-700 text-sm">&larr; Back</a>
            <button
                type="submit"
                class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2.5 px-6 rounded-lg transition"
            >
                Complete Installation
            </button>
        </div>
    </form>
@endsection
