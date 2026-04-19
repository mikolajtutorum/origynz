<!DOCTYPE html>
@php
    $direction = config('app.locale_meta.'.app()->getLocale().'.direction', 'ltr');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $direction }}" class="h-full bg-[#efefef]">
    <head>
        @include('partials.head')
    </head>
    <body class="h-full bg-[#efefef] text-[#474747] tree-workspace-page">

        <div class="flex min-h-screen flex-col">

            <x-app-header :active-nav="$activeNav ?? (request()->routeIs('dashboard', 'family-events.*') ? 'home' : (request()->routeIs('media.*') ? 'photos' : (request()->routeIs('trees.*') ? 'family-tree' : (request()->routeIs('profile.edit', 'security.edit', 'appearance.edit') ? 'settings' : null))))" />

            <main class="flex-1">
                {{ $slot }}
            </main>

        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
