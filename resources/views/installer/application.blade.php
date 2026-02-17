@extends('installer.layout')

@section('content')
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Application Settings</h2>

    <form method="POST" action="{{ route('install.application') }}" class="space-y-5">
        @csrf

        <div>
            <label for="app_name" class="text-sm font-medium text-gray-700">Application Name</label>
            <input
                type="text"
                id="app_name"
                name="app_name"
                value="{{ old('app_name', $old['app_name']) }}"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
                required
            >
        </div>

        <div>
            <label for="app_url" class="text-sm font-medium text-gray-700">Application URL</label>
            <input
                type="text"
                id="app_url"
                name="app_url"
                value="{{ old('app_url', $old['app_url']) }}"
                placeholder="https://your-domain.com"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
                required
            >
        </div>

        <div>
            <label for="app_env" class="text-sm font-medium text-gray-700">Environment</label>
            <select
                id="app_env"
                name="app_env"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border"
            >
                <option value="production" {{ old('app_env', $old['app_env']) === 'production' ? 'selected' : '' }}>production</option>
                <option value="local" {{ old('app_env', $old['app_env']) === 'local' ? 'selected' : '' }}>local</option>
                <option value="staging" {{ old('app_env', $old['app_env']) === 'staging' ? 'selected' : '' }}>staging</option>
            </select>
            <p class="mt-1 text-xs text-gray-500">Use 'production' for live servers. 'local' enables debug mode.</p>
        </div>

        <div class="flex items-center justify-between pt-4">
            <a href="{{ route('install.migrations') }}" class="text-gray-500 hover:text-gray-700 text-sm">&larr; Back</a>
            <button
                type="submit"
                class="bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 px-6 rounded-lg transition"
            >
                Save &amp; Continue
            </button>
        </div>
    </form>
@endsection
