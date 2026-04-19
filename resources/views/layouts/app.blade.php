<x-layouts::app.sidebar :title="$title ?? null" :active-nav="$activeNav ?? null">
    <flux:main>
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
