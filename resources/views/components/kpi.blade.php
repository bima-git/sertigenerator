@props(['label', 'value', 'icon', 'tone' => 'brand', 'note' => null, 'i' => 0])
@php($iconBg = ['brand' => 'bg-brand', 'accent' => 'bg-accent', 'success' => 'bg-success', 'info' => 'bg-info'][$tone])
<div class="tile tile-{{ $tone }}" data-reveal style="--i: {{ $i }}">
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-medium text-fg">{{ $label }}</p>
        <span class="tile-icon {{ $iconBg }}"><i class="ph-fill {{ $icon }}"></i></span>
    </div>
    <p class="num mt-5 text-[32px] font-semibold leading-none text-fg" data-count="{{ $value }}">{{ $value }}</p>
    @if ($note)
        <p class="mt-2 text-xs text-body">{{ $note }}</p>
    @endif
</div>
