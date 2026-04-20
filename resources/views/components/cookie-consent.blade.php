<div
    id="cookie-consent-banner"
    class="fixed bottom-0 inset-x-0 z-50 bg-white border-t border-[#e3e8ee] shadow-lg px-6 py-4"
    style="display:none"
>
    <div class="mx-auto max-w-5xl flex flex-col sm:flex-row items-start sm:items-center gap-4">
        <p class="flex-1 text-sm text-[#474747]">
            We use essential cookies to keep you signed in and remember your preferences. No advertising or tracking cookies are used.
            <a href="{{ route('legal.privacy') }}" class="underline text-blue-600 hover:text-blue-700">Learn more</a>.
        </p>
        <div class="flex items-center gap-3 shrink-0">
            <button
                id="cookie-decline-btn"
                class="rounded-xl border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-[#474747] hover:bg-zinc-50 transition"
            >
                {{ __('Decline non-essential') }}
            </button>
            <button
                id="cookie-accept-btn"
                class="rounded-xl bg-[#474747] px-4 py-2 text-sm font-medium text-white hover:bg-[#2d2d2d] transition"
            >
                {{ __('Accept') }}
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var banner = document.getElementById('cookie-consent-banner');
    if (!banner) return;
    if (!localStorage.getItem('cookie_consent')) {
        banner.style.display = '';
    }
    document.getElementById('cookie-accept-btn').addEventListener('click', function () {
        localStorage.setItem('cookie_consent', '1');
        banner.style.display = 'none';
    });
    document.getElementById('cookie-decline-btn').addEventListener('click', function () {
        localStorage.setItem('cookie_consent', '0');
        banner.style.display = 'none';
    });
})();
</script>
