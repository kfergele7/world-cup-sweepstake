@props([
    'value',
    'label' => 'Copy link',
    'buttonLabel' => 'Copy link',
    'copiedLabel' => 'Copied',
])

<button
    type="button"
    {{ $attributes->class('sk-btn-pill inline-flex items-center gap-2') }}
    data-copy-button
    data-copy-value="{{ $value }}"
    data-copy-label="{{ $buttonLabel }}"
    data-copy-copied-label="{{ $copiedLabel }}"
    aria-label="{{ $label }}"
>
    <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none">
        <path d="M8 8.5A2.5 2.5 0 0 1 10.5 6H17a2.5 2.5 0 0 1 2.5 2.5V15a2.5 2.5 0 0 1-2.5 2.5h-6.5A2.5 2.5 0 0 1 8 15V8.5Z" stroke="currentColor" stroke-width="1.8" />
        <path d="M6 15.5A2.5 2.5 0 0 1 4.5 13.2V6.5A2.5 2.5 0 0 1 7 4h6.7A2.5 2.5 0 0 1 16 5.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
    </svg>
    <span data-copy-text>{{ $buttonLabel }}</span>
</button>
