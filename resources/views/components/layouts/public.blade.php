<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Dynamic page title --}}
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    {{-- Fonts & Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex flex-col bg-gray-50 text-gray-800">
    {{-- ======================== --}}
    {{-- Page Content --}}
    {{-- ======================== --}}
    <main class="w-full">
        <div class="">
            {{ $slot }}
        </div>
    </main>

    {{-- ======================== --}}
    {{-- Footer --}}
    {{-- ======================== --}}
    <footer class="bg-gray-900 text-gray-300 py-8 mt-10">
        <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <p class="text-sm">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <div class="flex gap-4 text-sm">
                <a href="{{ route('privacy.policy') }}" class="hover:text-primary">Privacy</a>
                <a href="{{ route('terms.of.service') }}" class="hover:text-primary">Terms</a>
            </div>
        </div>
    </footer>

    {{-- Toast area for notifications (MaryUI) --}}
    <x-toast />
</body>
</html>
