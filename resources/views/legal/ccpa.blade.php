<x-layouts::legal>
    <div class="prose prose-sm max-w-none text-[#474747]">
        <h1 class="text-2xl font-bold text-[#2d2d2d] mb-2">California Privacy Rights (CCPA / CPRA)</h1>
        <p class="text-sm text-zinc-500 mb-6">Last updated: {{ \Carbon\Carbon::parse('2026-04-19')->format('F j, Y') }}</p>

        <p>This page explains the privacy rights available to California residents under the California Consumer Privacy Act (CCPA) and California Privacy Rights Act (CPRA), and allows you to exercise your opt-out right.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">Do We Sell Personal Information?</h2>
        <p>No. Origynz does not sell, rent, or share your personal information with third parties for their own marketing or commercial purposes. We do not engage in targeted advertising using your personal data.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">Your California Rights</h2>
        <p>As a California resident, you have the right to:</p>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>Know</strong> what personal information we collect, use, disclose, and sell about you.</li>
            <li><strong>Delete</strong> personal information we have collected from you (subject to certain exceptions).</li>
            <li><strong>Correct</strong> inaccurate personal information we maintain about you.</li>
            <li><strong>Opt out</strong> of the sale or sharing of your personal information (see below).</li>
            <li><strong>Limit use</strong> of sensitive personal information.</li>
            <li><strong>Non-discrimination</strong> — we will not discriminate against you for exercising these rights.</li>
        </ul>

        <h2 class="text-lg font-semibold mt-6 mb-2">Categories of Personal Information We Collect</h2>
        <p>We collect the following categories as defined by the CCPA:</p>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>Identifiers:</strong> Name, email address, IP address.</li>
            <li><strong>Personal information (Cal. Civ. Code § 1798.80):</strong> Name, email address, date of birth.</li>
            <li><strong>Sensitive personal information:</strong> Family relationships, health-related data you choose to enter (cause of death, physical description). We use this solely to provide the genealogy service.</li>
            <li><strong>Internet or network activity:</strong> Activity logs (pages visited, actions taken) for security purposes.</li>
            <li><strong>Geolocation:</strong> Country of residence (if you provide it).</li>
        </ul>
        <p>We do not collect information about race or ethnicity, religion, union membership, genetic data, biometric identifiers, or financial information.</p>

        <h2 class="text-lg font-semibold mt-6 mb-2">How to Submit a Request</h2>
        <p>To submit a request to know, delete, or correct your personal information:</p>
        <ul class="list-disc pl-5 space-y-1">
            <li><strong>Self-service:</strong> Download your data from <a href="{{ route('settings.data-export') }}" class="text-blue-600 underline">Settings → Data Export</a>. Delete your account from Settings → Delete Account.</li>
            <li><strong>Email:</strong> <a href="mailto:privacy@origynz.com" class="text-blue-600 underline">privacy@origynz.com</a> — we will respond within 45 days.</li>
        </ul>

        <h2 class="text-lg font-semibold mt-6 mb-2">Opt Out of Sale / Sharing</h2>
        <p>Although we do not sell personal information, California law gives you the right to opt out. Use the control below to record your preference — if we ever change our data-sharing practices, your preference will be honoured.</p>

        @if (session('status'))
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 mb-4">
                {{ session('status') }}
            </div>
        @endif

        @auth
            <div class="mt-4 rounded-xl border border-[#e3e8ee] p-5 not-prose">
                <p class="text-sm font-medium text-[#474747] mb-1">Current preference:</p>
                <p class="text-sm mb-4">
                    @if ($optedOut)
                        <span class="inline-flex items-center gap-1.5 text-green-700 font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Opted out — your data will not be sold or shared.
                        </span>
                    @else
                        <span class="text-zinc-500">Not opted out — default setting (we do not sell data).</span>
                    @endif
                </p>
                <form method="POST" action="{{ route('legal.ccpa.store') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center rounded-xl border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-[#474747] shadow-sm hover:bg-zinc-50 transition">
                        {{ $optedOut ? __('Remove opt-out preference') : __('Opt out of sale / sharing') }}
                    </button>
                </form>
            </div>
        @else
            <div class="mt-4 rounded-xl border border-zinc-200 bg-zinc-50 px-5 py-4 text-sm text-zinc-600 not-prose">
                <a href="{{ route('login') }}" class="text-blue-600 underline font-medium">Log in</a> to manage your opt-out preference.
            </div>
        @endauth

        <h2 class="text-lg font-semibold mt-6 mb-2">Contact</h2>
        <p>Email: <a href="mailto:privacy@origynz.com" class="text-blue-600 underline">privacy@origynz.com</a></p>
    </div>
</x-layouts::legal>
