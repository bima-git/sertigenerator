@props(['label', 'value', 'icon', 'tone' => 'brand', 'note' => null, 'i' => 0, 'spark' => []])
@php
    $iconBg = ['brand' => 'bg-brand', 'accent' => 'bg-accent', 'success' => 'bg-success', 'info' => 'bg-info'][$tone];
    $sparkColor = ['brand' => 'text-brand', 'accent' => 'text-accent', 'success' => 'text-success', 'info' => 'text-info'][$tone];
    $max = max([1, ...$spark]);
@endphp
<div class="tile tile-{{ $tone }}" data-reveal style="--i: {{ $i }}">
    <div class="flex items-start justify-between gap-3">
        {{-- <wbr> setelah garis miring: "Seminar/Webinar" boleh patah baris di ponsel. --}}
        <p class="min-w-0 text-sm font-medium text-fg">{!! str_replace('/', '/<wbr>', e($label)) !!}</p>
        <span class="tile-icon flex-none {{ $iconBg }}"><i class="ph-fill {{ $icon }}"></i></span>
    </div>
    <p class="num mt-5 text-[32px] font-semibold leading-none text-fg">
        <span class="count" style="--to: {{ (int) $value }}; --i: {{ $i }}" aria-hidden="true"></span>
        <span class="sr-only">{{ $value }}</span>
    </p>
    @if ($note)
        <p class="mt-2 text-xs text-body max-w-[60%]">{{ $note }}</p>
    @endif
    @if ($spark)
        <span class="spark {{ $sparkColor }}" aria-hidden="true">
            @foreach ($spark as $v)
                <i style="height: {{ max(12, round($v / $max * 100)) }}%; --b: {{ $loop->index }}"></i>
            @endforeach
        </span>
    @endif
</div>
