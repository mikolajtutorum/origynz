<!DOCTYPE html>
@php
    $direction = config('app.locale_meta.'.app()->getLocale().'.direction', 'ltr');
@endphp
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $direction }}" class="h-full bg-[#efefef]">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Origynz') }} | Genealogy Workspace</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="h-full bg-[#efefef] text-[#474747] tree-workspace-page">

        <div class="flex min-h-screen flex-col">

            <x-app-header active-nav="home" :authenticated="false">
                <x-slot:topbar-right-extra>
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded bg-[#2563eb] px-4 py-1.5 font-medium text-white hover:bg-[#1d4ed8]">{{ __('Open workspace') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="opacity-80 hover:opacity-100">{{ __('Log in') }}</a>
                        <a href="{{ route('register') }}" class="rounded bg-[#2563eb] px-4 py-1.5 font-medium text-white hover:bg-[#1d4ed8]">{{ __('Create account') }}</a>
                    @endauth
                </x-slot:topbar-right-extra>
            </x-app-header>

            <main class="flex-1">
                <div class="mx-auto w-full max-w-[1200px] px-8 py-12">
                    <div class="grid gap-12 lg:grid-cols-[1.15fr_.85fr] lg:items-center">

                        <section class="space-y-8">
                            <div class="space-y-5">
                                <p class="inline-flex rounded-full border border-[#d7d7d7] bg-white px-4 py-2 text-xs font-semibold uppercase tracking-[0.28em] text-[#5f6a74] shadow-sm">
                                    {{ __('Build your family story') }}
                                </p>
                                <h1 class="max-w-4xl text-5xl font-semibold tracking-tight text-[#1f252b] sm:text-6xl">
                                    {{ __('Research, map, and preserve generations in a single shared family tree.') }}
                                </h1>
                                <p class="max-w-2xl text-lg leading-8 text-[#4f5963]">
                                    {{ __('Start with a living tree, add people and relationship links, then grow into timelines, records, photos, branch views, and research workflows. This app is being built as a genealogy platform from the ground up.') }}
                                </p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-3">
                                <div class="rounded-xl border border-[#e3e8ee] bg-white p-5 shadow-sm">
                                    <p class="text-sm font-semibold text-[#1f252b]">{{ __('Core trees') }}</p>
                                    <p class="mt-2 text-sm leading-6 text-[#6f7b83]">{{ __('Private tree workspaces with people, notes, and relationship links.') }}</p>
                                </div>
                                <div class="rounded-xl border border-[#e3e8ee] bg-white p-5 shadow-sm">
                                    <p class="text-sm font-semibold text-[#1f252b]">{{ __('Profiles') }}</p>
                                    <p class="mt-2 text-sm leading-6 text-[#6f7b83]">{{ __('Biographical records with names, dates, places, and life summaries.') }}</p>
                                </div>
                                <div class="rounded-xl border border-[#e3e8ee] bg-white p-5 shadow-sm">
                                    <p class="text-sm font-semibold text-[#1f252b]">{{ __('Genealogy-ready') }}</p>
                                    <p class="mt-2 text-sm leading-6 text-[#6f7b83]">{{ __('Structured to expand toward records, media, DNA, and collaboration.') }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                @auth
                                    <a href="{{ route('dashboard') }}" class="rounded-full bg-[#2563eb] px-6 py-3 text-sm font-medium text-white transition hover:bg-[#1d4ed8]">{{ __('Open workspace') }}</a>
                                @else
                                    <a href="{{ route('register') }}" class="rounded-full bg-[#2563eb] px-6 py-3 text-sm font-medium text-white transition hover:bg-[#1d4ed8]">{{ __('Get started') }}</a>
                                    <a href="{{ route('login') }}" class="rounded-full border border-[#d7d7d7] bg-white px-6 py-3 text-sm font-medium text-[#4f5963] transition hover:border-[#aab4bd]">{{ __('Log in') }}</a>
                                @endauth
                            </div>
                        </section>

                        <section>
                            <div class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-[0_8px_28px_rgba(0,0,0,.08)]">
                                <div class="rounded-xl bg-[#1f252b] p-5 text-white">
                                    <p class="text-xs uppercase tracking-[0.3em] text-white/50">{{ __('Phase 1') }}</p>
                                    <h2 class="mt-3 text-2xl font-semibold">{{ __('A serious genealogy foundation') }}</h2>
                                    <p class="mt-3 text-sm leading-6 text-white/70">{{ __('This build starts with the part that matters most: a durable family-tree data model and a workspace people can actually use.') }}</p>
                                </div>

                                <div class="mt-5 grid gap-4">
                                    <div class="rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] p-5">
                                        <p class="text-sm font-semibold text-[#1f252b]">{{ __('Included now') }}</p>
                                        <ul class="mt-3 space-y-2 text-sm text-[#6f7b83]">
                                            <li>{{ __('Tree creation and privacy modes') }}</li>
                                            <li>{{ __('People profiles with names, places, notes, and lifespans') }}</li>
                                            <li>{{ __('Parent, child, and spouse links') }}</li>
                                        </ul>
                                    </div>
                                    <div class="rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] p-5">
                                        <p class="text-sm font-semibold text-[#1f252b]">{{ __('Planned next') }}</p>
                                        <ul class="mt-3 space-y-2 text-sm text-[#6f7b83]">
                                            <li>{{ __('Visual pedigree and descendant charts') }}</li>
                                            <li>{{ __('Events, sources, and photo galleries') }}</li>
                                            <li>{{ __('Relative invitations and collaborative editing') }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </section>

                    </div>
                </div>
            </main>

        </div>

    </body>
</html>
