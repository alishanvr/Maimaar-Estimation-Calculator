@extends('installer.layout')

@section('content')
    <h2 class="text-2xl font-bold text-gray-900 mb-2">Server Requirements</h2>
    <p class="text-gray-600 mb-6">Ensure your server meets all requirements before proceeding.</p>

    <div class="space-y-3 mb-8">
        @foreach ($requirements as $requirement)
            <div class="flex items-start justify-between p-4 rounded-lg border {{ $requirement['passed'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                <div class="flex items-start gap-3">
                    @if ($requirement['passed'])
                        <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @else
                        <svg class="w-5 h-5 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif

                    <div>
                        <span class="font-medium {{ $requirement['passed'] ? 'text-green-800' : 'text-red-800' }}">
                            {{ $requirement['name'] }}
                        </span>
                        @if (!empty($requirement['note']))
                            <p class="text-sm mt-0.5 {{ $requirement['passed'] ? 'text-green-600' : 'text-red-600' }}">
                                {{ $requirement['note'] }}
                            </p>
                        @endif
                    </div>
                </div>

                <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $requirement['passed'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $requirement['passed'] ? 'Passed' : 'Failed' }}
                </span>
            </div>
        @endforeach
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('install.welcome') }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium transition-colors duration-150">
            &larr; Back
        </a>

        @if ($allPassed)
            <a href="{{ route('install.database') }}"
               class="inline-flex items-center gap-2 bg-brand-600 hover:bg-brand-700 text-white font-semibold py-2.5 px-6 rounded-lg transition-colors duration-150">
                Continue
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        @else
            <div class="text-right">
                <button disabled
                        class="inline-flex items-center gap-2 bg-gray-300 text-gray-500 font-semibold py-2.5 px-6 rounded-lg cursor-not-allowed">
                    Continue
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </button>
                <p class="text-xs text-red-600 mt-1">Please fix the issues above before continuing</p>
            </div>
        @endif
    </div>
@endsection
