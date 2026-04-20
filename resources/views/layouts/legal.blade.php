<!DOCTYPE html>
@php
    $direction = config('app.locales.'.app()->getLocale().'.direction', 'ltr');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $direction }}" class="h-full bg-[#efefef]">
    <head>
        @include('partials.head')
    </head>
    <body class="h-full bg-[#efefef] text-[#474747]">

        <div class="flex min-h-screen flex-col">

            <x-app-header />

            <main class="flex-1 px-6 py-10">
                <div class="mx-auto w-full max-w-3xl">
                    <div class="rounded-2xl border border-[#e3e8ee] bg-white p-8 shadow-sm">
                        {{ $slot }}
                    </div>
                </div>
            </main>

            <x-legal-footer />

        </div>

        <x-cookie-consent />

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
