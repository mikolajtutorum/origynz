{{--
    Profile discussion thread partial.
    Required variables: $person (Person model)
    Optional: $discussions (Collection — if not passed, they are loaded here)
--}}
@php
    $discussions ??= $person->discussions()->get();
@endphp

<section class="rounded-2xl border border-[#e3e8ee] bg-white shadow-sm" x-data="{ replyTo: null }">
    <div class="border-b border-[#f0f4f8] px-6 py-4">
        <h2 class="text-sm font-semibold uppercase tracking-widest text-[#6f7b83]">
            {{ __('Discussion') }}
            <span class="ml-2 rounded-full bg-[#f0f4f8] px-2 py-0.5 text-xs font-normal text-[#9daab4]">
                {{ $discussions->count() }}
            </span>
        </h2>
    </div>

    {{-- Post top-level comment --}}
    <div class="border-b border-[#f0f4f8] px-6 py-4">
        <form method="POST" action="{{ route('people.discussions.store', $person) }}">
            @csrf
            <textarea name="body" rows="3" required maxlength="2000"
                      placeholder="{{ __('Share a note, question, or correction about this profile…') }}"
                      class="w-full resize-none rounded-xl border border-[#cdd7e1] bg-[#f7f9fb] px-4 py-3 text-sm text-[#1f252b] placeholder-[#9daab4] focus:border-[#93c5fd] focus:outline-none"></textarea>
            <div class="mt-2 flex justify-end">
                <button type="submit"
                        class="rounded-[6px] bg-[#2563eb] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#1d4ed8]">
                    {{ __('Post Comment') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Thread list --}}
    @forelse ($discussions as $comment)
        <div class="border-b border-[#f0f4f8] px-6 py-4 last:border-0">
            <div class="flex gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#dbeafe] text-xs font-semibold text-[#2563eb]">
                    {{ mb_strtoupper(mb_substr($comment->user?->name ?? '?', 0, 1)) }}
                </div>
                <div class="flex-1">
                    <div class="flex items-baseline gap-2">
                        <span class="text-sm font-semibold text-[#1f252b]">{{ $comment->user?->name }}</span>
                        <span class="text-xs text-[#9daab4]">{{ $comment->created_at->diffForHumans() }}</span>
                    </div>

                    @if ($comment->is_deleted)
                        <p class="mt-1 text-sm italic text-[#9daab4]">{{ __('[comment removed]') }}</p>
                    @else
                        <p class="mt-1 text-sm leading-6 text-[#1f252b]">{{ $comment->body }}</p>

                        <div class="mt-2 flex gap-3 text-xs text-[#9daab4]">
                            <button @click="replyTo = (replyTo === '{{ $comment->id }}' ? null : '{{ $comment->id }}')"
                                    class="hover:text-[#2563eb]">
                                {{ __('Reply') }}
                            </button>
                            @if (auth()->id() === $comment->user_id || auth()->user()?->hasAnyRole(['super admin', 'admin', 'curator']))
                                <form method="POST" action="{{ route('people.discussions.destroy', $comment) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="hover:text-[#dc2626]">{{ __('Remove') }}</button>
                                </form>
                            @endif
                        </div>

                        {{-- Inline reply form --}}
                        <div x-show="replyTo === '{{ $comment->id }}'" class="mt-3">
                            <form method="POST" action="{{ route('people.discussions.store', $person) }}">
                                @csrf
                                <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                <textarea name="body" rows="2" required maxlength="2000"
                                          placeholder="{{ __('Write a reply…') }}"
                                          class="w-full resize-none rounded-xl border border-[#cdd7e1] bg-[#f7f9fb] px-3 py-2 text-sm focus:border-[#93c5fd] focus:outline-none"></textarea>
                                <div class="mt-1.5 flex justify-end gap-2">
                                    <button type="button" @click="replyTo = null" class="text-xs text-[#9daab4] hover:text-[#1f252b]">{{ __('Cancel') }}</button>
                                    <button type="submit" class="rounded-[6px] bg-[#2563eb] px-3 py-1 text-xs font-medium text-white hover:bg-[#1d4ed8]">{{ __('Reply') }}</button>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- Replies --}}
                    @if ($comment->replies->isNotEmpty())
                        <div class="mt-3 space-y-3 border-l-2 border-[#e3e8ee] pl-4">
                            @foreach ($comment->replies as $reply)
                                <div class="flex gap-3">
                                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#f0f4f8] text-xs font-semibold text-[#6f7b83]">
                                        {{ mb_strtoupper(mb_substr($reply->user?->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-baseline gap-2">
                                            <span class="text-sm font-semibold text-[#1f252b]">{{ $reply->user?->name }}</span>
                                            <span class="text-xs text-[#9daab4]">{{ $reply->created_at->diffForHumans() }}</span>
                                        </div>
                                        @if ($reply->is_deleted)
                                            <p class="mt-1 text-sm italic text-[#9daab4]">{{ __('[comment removed]') }}</p>
                                        @else
                                            <p class="mt-1 text-sm leading-6 text-[#1f252b]">{{ $reply->body }}</p>
                                            @if (auth()->id() === $reply->user_id || auth()->user()?->hasAnyRole(['super admin', 'admin', 'curator']))
                                                <form method="POST" action="{{ route('people.discussions.destroy', $reply) }}" class="mt-1 inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-xs text-[#9daab4] hover:text-[#dc2626]">{{ __('Remove') }}</button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @empty
        <div class="px-6 py-10 text-center text-sm text-[#9daab4]">
            {{ __('No comments yet. Be the first to start a discussion.') }}
        </div>
    @endforelse
</section>
