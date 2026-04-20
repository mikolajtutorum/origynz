<footer class="border-t border-[#e3e8ee] bg-white px-6 py-4">
    <div class="mx-auto max-w-5xl flex flex-wrap items-center justify-center gap-x-4 gap-y-1 text-xs text-zinc-500">
        <span>© {{ date('Y') }} Origynz</span>
        <span class="hidden sm:inline">·</span>
        <a href="{{ route('legal.privacy') }}" class="hover:text-zinc-700 transition">{{ __('Privacy Policy') }}</a>
        <span>·</span>
        <a href="{{ route('legal.terms') }}" class="hover:text-zinc-700 transition">{{ __('Terms of Service') }}</a>
        <span>·</span>
        <a href="{{ route('legal.dpa') }}" class="hover:text-zinc-700 transition">{{ __('Data Processing Agreement') }}</a>
        <span>·</span>
        <a href="{{ route('legal.ccpa') }}" class="hover:text-zinc-700 transition">{{ __('Do Not Sell My Info') }}</a>
        @auth
            <span>·</span>
            <a href="{{ route('settings.data-export') }}" class="hover:text-zinc-700 transition">{{ __('Export My Data') }}</a>
        @endauth
    </div>
</footer>
