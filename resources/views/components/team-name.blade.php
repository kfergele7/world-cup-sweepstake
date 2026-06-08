@props(['team'])

<span {{ $attributes->class('inline-flex min-w-0 items-center gap-1.5') }}>
    @if ($flag = $team->displayFlag())
        <span aria-hidden="true" class="shrink-0 text-base leading-none">{{ $flag }}</span>
    @endif
    <span class="min-w-0 truncate">{{ $team->name }}</span>
</span>
