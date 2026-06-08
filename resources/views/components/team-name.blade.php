@props(['team'])

<span {{ $attributes->class('inline-flex min-w-0 items-center gap-1.5') }}>
    @if ($flag = $team->displayFlag())
        @if (preg_match('/^[A-Z]{2,3}$/', $flag))
            <span aria-hidden="true" class="shrink-0 rounded border border-brand-border bg-white px-1 py-0.5 text-[0.65rem] font-bold leading-none text-brand-muted">{{ $flag }}</span>
        @else
            <span aria-hidden="true" class="shrink-0 text-base leading-none">{{ $flag }}</span>
        @endif
    @endif
    <span class="min-w-0 truncate">{{ $team->name }}</span>
</span>
