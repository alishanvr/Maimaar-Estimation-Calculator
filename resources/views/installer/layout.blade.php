<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - {{ config('app.name', 'Maimaar') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e3a5f', 900: '#1e293b' }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-3xl mx-auto py-8 px-4">
        {{-- Header --}}
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-brand-800">{{ config('app.name', 'Maimaar') }}</h1>
            <p class="text-gray-500 mt-1">Installation Wizard</p>
        </div>

        {{-- Step Progress --}}
        @php
            $stepLabels = ['Welcome', 'Requirements', 'Database', 'Migrate', 'Application', 'Mail', 'Admin', 'Finalize'];
            $currentStep = $step ?? 0;
        @endphp
        <div class="mb-8">
            <div class="flex items-center justify-between">
                @foreach($stepLabels as $index => $label)
                    <div class="flex flex-col items-center flex-1">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                            {{ $index < $currentStep ? 'bg-green-500 text-white' : ($index === $currentStep ? 'bg-brand-600 text-white' : 'bg-gray-200 text-gray-500') }}">
                            @if($index < $currentStep)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>
                        <span class="text-xs mt-1 {{ $index === $currentStep ? 'text-brand-700 font-semibold' : 'text-gray-400' }}">{{ $label }}</span>
                    </div>
                    @if(!$loop->last)
                        <div class="flex-1 h-0.5 mx-1 mt-[-16px] {{ $index < $currentStep ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Content Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 md:p-8">
            @yield('content')
        </div>

        {{-- Footer --}}
        <div class="text-center mt-6 text-gray-400 text-sm">
            &copy; {{ date('Y') }} {{ config('app.name', 'Maimaar') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
