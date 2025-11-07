<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased bg-base-200">

    {{-- NAVBAR mobile only --}}
    <x-nav sticky class="lg:hidden">
        <x-slot:brand>
            <image src="{{ Vite::asset('resources/images/logo.svg') }}" alt="Logo" class="h-8 w-auto" />
        </x-slot:brand>
        <x-slot:actions>
            <label for="main-drawer" class="lg:hidden me-3">
                <x-icon name="o-bars-3" class="cursor-pointer" />
            </label>
        </x-slot:actions>
    </x-nav>

    {{-- MAIN --}}
    <x-main full-width>
        {{-- SIDEBAR --}}
        <x-slot:sidebar drawer="main-drawer" collapsible class="bg-base-100 lg:bg-inherit">

            {{-- BRAND --}}
            <image src="{{ Vite::asset('resources/images/logo.svg') }}" alt="Logo" class="h-14 w-auto px-5 pt-2" />


            {{-- MENU --}}
            <x-menu activate-by-route>

                {{-- User --}}
                @if($user = auth()->user())
                    <x-menu-separator />

                    <x-list-item :item="$user" value="name" sub-value="email" no-separator no-hover class="-mx-2 !-my-2 rounded">
                        <x-slot:actions>
                            <x-button icon="o-power" class="btn-circle btn-ghost btn-xs" tooltip-left="logoff" no-wire-navigate link="/logout" />
                        </x-slot:actions>
                    </x-list-item>

                    <x-menu-separator />
                @endif

                <x-menu-item title="Users" icon="o-users" link="{{ route('admin.users') }}"  route="admin.users" />
                <x-menu-item title="Pay Periods" icon="o-currency-dollar" link="{{ route('admin.pay-periods.index') }}" route="admin.pay-periods.*" />

                <x-menu-separator />

                <x-menu-item title="Candidates" icon="o-user-group" link="{{ route('admin.candidates.index') }}" route="admin.candidates.*" />
                <x-menu-item title="Typing Samples" icon="o-document-text" link="{{ route('admin.typing-samples.index') }}" route="admin.typing-samples.*" />

                <x-menu-separator />

                <x-menu-item title="Job Positions" icon="o-briefcase" link="{{ route('admin.positions.index') }}" route="admin.positions.*" />
                <x-menu-item title="Applications" icon="o-inbox" link="{{ route('admin.applications.index') }}" route="admin.applications.*" />


            </x-menu>
        </x-slot:sidebar>

        {{-- The `$slot` goes here --}}
        <x-slot:content>
            {{ $slot }}
        </x-slot:content>
    </x-main>

    {{--  TOAST area --}}
    <x-toast />
</body>
</html>
