@props(['title', 'subtitle' => null])
<header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between mb-8" data-reveal>
    <div class="max-w-[65ch]">
        <h1 class="text-[28px] font-semibold">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1.5 text-muted">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endisset
</header>
