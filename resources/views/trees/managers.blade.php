<x-layouts::app :title="__('Tree Managers')" active-nav="family-tree">
    <div class="genealogy-shell space-y-6">
        <section class="overflow-hidden rounded-2xl border border-[#e3e8ee] bg-white px-8 py-7 shadow-sm">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-[#6f7b83]">{{ __('Tree managers') }}</p>
                    <h1 class="max-w-3xl text-4xl font-semibold tracking-tight text-[#1f252b] sm:text-5xl">
                        {{ $tree->name }}
                    </h1>
                    <p class="max-w-2xl text-base leading-7 text-[#4f5963]">
                        {{ __('Manage who participates in this tree, review incoming access requests, and track recent activity from current members.') }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('trees.show', $tree) }}" class="rounded-[6px] bg-[#2563eb] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#1d4ed8]">
                        {{ __('Open family tree') }}
                    </a>
                    <a href="{{ route('trees.manage') }}" class="rounded-[6px] border border-[#cdd7e1] bg-white px-4 py-2 text-sm font-medium text-[#475569] transition hover:border-[#93c5fd] hover:text-[#2563eb]">
                        {{ __('All trees') }}
                    </a>
                </div>
            </div>

            @if (session('status'))
                <div class="mt-6 rounded-xl border border-[#bfdbfe] bg-[#eff6ff] px-4 py-3 text-sm text-[#1e40af]">
                    {{ session('status') }}
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Members') }}</h2>
                    <p class="text-sm text-[#6f7b83]">{{ __('Current people with access to this tree.') }}</p>
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-xl border border-[#e3e8ee]">
                <table class="min-w-full divide-y divide-[#e3e8ee] text-sm">
                    <thead class="bg-[#f7f9fb] {{ config('app.locales.'.app()->getLocale().'.direction', 'ltr') === 'rtl' ? 'text-right' : 'text-left' }} text-[#5f6a74]">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Member name') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Access level') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Activity status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#e9eef3] bg-white text-[#334155]">
                        @foreach ($memberRows as $member)
                            <tr>
                                <td class="px-4 py-3 font-medium">{{ $member['name'] }}</td>
                                <td class="px-4 py-3">{{ $member['access_level'] }}</td>
                                <td class="px-4 py-3">{{ $member['last_visited'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[1fr_1fr]">
            <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Invite by email') }}</h2>
                <p class="mt-2 text-sm leading-6 text-[#6f7b83]">{{ __('Send an invitation to a collaborator who should gain access to this tree.') }}</p>

                <form method="POST" action="{{ route('trees.managers.invitations.store', $tree) }}" class="mt-5 space-y-4">
                    @csrf
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-[#4f5963]" for="invite_email">{{ __('Email address') }}</label>
                        <input
                            id="invite_email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            class="workspace-input"
                            placeholder="{{ __('cousin@example.com') }}"
                        />
                        @error('email')
                            <p class="text-sm text-[#b91c1c]">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-[#4f5963]" for="access_level">{{ __('Access level') }}</label>
                        <select id="access_level" name="access_level" class="workspace-input">
                            <option value="observer">{{ __('Tree observer') }}</option>
                            <option value="manager">{{ __('Tree manager') }}</option>
                        </select>
                        @error('access_level')
                            <p class="text-sm text-[#b91c1c]">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="workspace-primary-button">
                        {{ __('Create invitation') }}
                    </button>
                </form>

                <div class="mt-6 space-y-3">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.22em] text-[#6f7b83]">{{ __('Pending invitations') }}</h3>
                    @forelse ($pendingInvites as $invite)
                        <div class="rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] px-4 py-3 text-sm text-[#334155]">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <span class="font-medium">{{ $invite->email }}</span>
                                    <p class="mt-1 text-xs uppercase tracking-[0.18em] text-[#6f7b83]">{{ __('Tree :level', ['level' => $invite->access_level]) }}</p>
                                </div>
                                <span class="text-[#6f7b83]">{{ $invite->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 text-[#6f7b83]">{{ __('Invited by :name', ['name' => $invite->inviter->name]) }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-[#6f7b83]">{{ __('No pending invitations yet.') }}</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
                <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Membership requests') }}</h2>
                <p class="mt-2 text-sm leading-6 text-[#6f7b83]">{{ __('Review new requests from people who want access to this tree.') }}</p>

                <div class="mt-5 space-y-4">
                    @forelse ($pendingRequests as $membershipRequest)
                        <div class="rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-medium text-[#1f252b]">{{ $membershipRequest->requester_name }}</p>
                                    <p class="text-sm text-[#6f7b83]">{{ $membershipRequest->requester_email }}</p>
                                </div>
                                <span class="text-sm text-[#6f7b83]">{{ $membershipRequest->created_at->diffForHumans() }}</span>
                            </div>
                            @if ($membershipRequest->note)
                                <p class="mt-3 text-sm leading-6 text-[#4f5963]">{{ $membershipRequest->note }}</p>
                            @endif
                            <div class="mt-4 flex flex-wrap gap-3">
                                <form method="POST" action="{{ route('trees.managers.requests.review', [$tree, $membershipRequest]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="decision" value="approved" />
                                    <button type="submit" class="rounded-[6px] bg-[#2563eb] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#1d4ed8]">
                                        {{ __('Approve') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('trees.managers.requests.review', [$tree, $membershipRequest]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="decision" value="declined" />
                                    <button type="submit" class="rounded-[6px] border border-[#d7dde4] bg-white px-4 py-2 text-sm font-medium text-[#475569] transition hover:border-[#94a3b8] hover:text-[#1f2937]">
                                        {{ __('Decline') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-[#6f7b83]">{{ __('No pending membership requests right now.') }}</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- ── Global Tree Settings ──────────────────────────────── --}}
        <section class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-1">
                    <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Global Tree') }}</h2>
                    <p class="max-w-2xl text-sm leading-6 text-[#6f7b83]">
                        {{ __('When enabled, historical records from this tree are included in the Origynz Global Tree — a community-wide shared view. Living persons (or anyone born within the last 100 years with no death date) are always shown as "Private Person" with no personal details, regardless of this setting.') }}
                    </p>
                </div>
                @if ($tree->global_tree_enabled)
                    <span class="rounded-[6px] bg-[#dcfce7] px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-[#166534]">
                        {{ __('Active') }}
                    </span>
                @else
                    <span class="rounded-[6px] bg-[#f3f4f6] px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-[#6b7280]">
                        {{ __('Inactive') }}
                    </span>
                @endif
            </div>

            <div class="mt-6 rounded-xl border border-[#fde68a] bg-[#fffbeb] px-5 py-4 text-sm leading-6 text-[#78350f]">
                <strong>{{ __('Data-protection notice:') }}</strong>
                {{ __('By enabling this feature you confirm that the historical data in this tree was collected lawfully and that you are not knowingly sharing sensitive personal data (health conditions, genetic data, adoptions) about living individuals. Living persons are automatically anonymised. You can disable this toggle at any time; your data will be removed from the Global Tree immediately.') }}
            </div>

            <div class="mt-6 space-y-4">
                @if (! $tree->global_tree_enabled)
                    {{-- Enable form – requires consent checkbox --}}
                    <form
                        id="global-tree-enable-form"
                        method="POST"
                        action="{{ route('trees.global-tree-settings.update', $tree) }}"
                        class="space-y-4"
                    >
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="global_tree_enabled" value="1">

                        <label class="flex cursor-pointer items-start gap-3">
                            <input
                                id="global-tree-consent"
                                type="checkbox"
                                name="consent"
                                value="1"
                                required
                                class="mt-0.5 h-4 w-4 rounded border-[#cdd7e1] text-[#2563eb] focus:ring-[#93c5fd]"
                            >
                            <span class="text-sm leading-6 text-[#334155]">
                                {{ __('I confirm I have the right to share this data and understand that living persons will be anonymised in the Global Tree.') }}
                            </span>
                        </label>

                        @error('consent')
                            <p class="text-sm text-[#b91c1c]">{{ $message }}</p>
                        @enderror

                        <button
                            type="submit"
                            class="rounded-[6px] bg-[#2563eb] px-5 py-2 text-sm font-medium text-white transition hover:bg-[#1d4ed8]"
                        >
                            {{ __('Enable Global Tree for this tree') }}
                        </button>
                    </form>
                @else
                    {{-- Disable form --}}
                    <form
                        method="POST"
                        action="{{ route('trees.global-tree-settings.update', $tree) }}"
                    >
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="global_tree_enabled" value="0">
                        <input type="hidden" name="consent" value="1">
                        <button
                            type="submit"
                            class="rounded-[6px] border border-[#fca5a5] bg-[#fff1f2] px-5 py-2 text-sm font-medium text-[#b91c1c] transition hover:bg-[#fee2e2]"
                        >
                            {{ __('Remove this tree from the Global Tree') }}
                        </button>
                    </form>

                    <p class="text-sm text-[#6f7b83]">
                        {{ __('Your tree is currently visible in the') }}
                        <a href="{{ route('global-tree.index') }}" class="text-[#2563eb] hover:underline">{{ __('Global Tree') }}</a>.
                    </p>
                @endif
            </div>
        </section>

        <section class="rounded-2xl border border-[#e3e8ee] bg-white p-6 shadow-sm">
            <h2 class="text-xl font-semibold text-[#1f252b]">{{ __('Request reviews') }}</h2>
            <p class="mt-2 text-sm leading-6 text-[#6f7b83]">{{ __('A history of approved and declined membership requests for this tree.') }}</p>

            <div class="mt-5 space-y-3">
                @forelse ($reviewedRequests as $membershipRequest)
                    <div class="rounded-xl border border-[#e3e8ee] bg-[#f7f9fb] px-4 py-3 text-sm text-[#334155]">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-medium text-[#1f252b]">{{ $membershipRequest->requester_name }}</p>
                                <p class="text-[#6f7b83]">{{ $membershipRequest->requester_email }}</p>
                            </div>
                            <span class="rounded-[6px] px-3 py-1 text-xs font-semibold uppercase tracking-[0.22em] {{ $membershipRequest->status === 'approved' ? 'bg-[#dcfce7] text-[#166534]' : 'bg-[#fee2e2] text-[#991b1b]' }}">
                                {{ __($membershipRequest->status) }}
                            </span>
                        </div>
                        <p class="mt-2 text-[#6f7b83]">
                            {{ __('Reviewed :when by :name', ['when' => $membershipRequest->reviewed_at?->diffForHumans() ?? __('recently'), 'name' => $membershipRequest->reviewer?->name ?? __('Unknown reviewer')]) }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-[#6f7b83]">{{ __('No reviewed membership requests yet.') }}</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts::app>
