@extends('installer.layout')

@section('content')
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Database Configuration</h2>
    <p class="text-gray-600 mb-6">Enter your database connection details below.</p>

    <form method="POST" action="{{ route('install.database') }}" class="space-y-5">
        @csrf

        <div>
            <label for="db_connection" class="text-sm font-medium text-gray-700 block mb-1">
                Database Connection
            </label>
            <select name="db_connection"
                    id="db_connection"
                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border bg-white">
                <option value="mysql" {{ old('db_connection', $old['db_connection'] ?? 'mysql') === 'mysql' ? 'selected' : '' }}>MySQL</option>
                <option value="sqlite" {{ old('db_connection', $old['db_connection'] ?? '') === 'sqlite' ? 'selected' : '' }}>SQLite</option>
                <option value="pgsql" {{ old('db_connection', $old['db_connection'] ?? '') === 'pgsql' ? 'selected' : '' }}>PostgreSQL</option>
            </select>
            @error('db_connection')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="sm:col-span-2">
                <label for="db_host" class="text-sm font-medium text-gray-700 block mb-1">
                    Host
                </label>
                <input type="text"
                       name="db_host"
                       id="db_host"
                       value="{{ old('db_host', $old['db_host'] ?? '127.0.0.1') }}"
                       placeholder="127.0.0.1"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border">
                @error('db_host')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="db_port" class="text-sm font-medium text-gray-700 block mb-1">
                    Port
                </label>
                <input type="text"
                       name="db_port"
                       id="db_port"
                       value="{{ old('db_port', $old['db_port'] ?? '3306') }}"
                       placeholder="3306"
                       class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border">
                @error('db_port')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="db_database" class="text-sm font-medium text-gray-700 block mb-1">
                Database Name
            </label>
            <input type="text"
                   name="db_database"
                   id="db_database"
                   value="{{ old('db_database', $old['db_database'] ?? '') }}"
                   placeholder="my_database"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border">
            @error('db_database')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="db_username" class="text-sm font-medium text-gray-700 block mb-1">
                Username
            </label>
            <input type="text"
                   name="db_username"
                   id="db_username"
                   value="{{ old('db_username', $old['db_username'] ?? '') }}"
                   placeholder="root"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border">
            @error('db_username')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="db_password" class="text-sm font-medium text-gray-700 block mb-1">
                Password
            </label>
            <input type="password"
                   name="db_password"
                   id="db_password"
                   placeholder="Leave blank if no password"
                   class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 px-3 py-2 border">
            @error('db_password')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between pt-2">
            <a href="{{ route('install.requirements') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium transition-colors duration-150">
                &larr; Back
            </a>

            <button type="submit"
                    class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 px-6 rounded-lg transition-colors duration-150">
                Test & Save Database Settings
            </button>
        </div>
    </form>
@endsection
