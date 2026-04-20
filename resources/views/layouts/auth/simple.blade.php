<!DOCTYPE html>
@php
    $direction = config('app.locales.'.app()->getLocale().'.direction', 'ltr');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $direction }}" class="h-full bg-[#efefef]">
    <head>
        @include('partials.head')
    </head>
    <body class="h-full bg-[#efefef] text-[#474747] tree-workspace-page">

        <div class="flex min-h-screen flex-col">

            <x-app-header :authenticated="false" />

            <main class="flex flex-1 items-center justify-center px-6 py-12">
                <div class="w-full max-w-sm">
                    <div class="rounded-2xl border border-[#e3e8ee] bg-white p-8 shadow-sm">
                        {{ $slot }}
                    </div>
                </div>
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
