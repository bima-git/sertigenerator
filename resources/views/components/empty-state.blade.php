@props(['icon' => 'ph-tray', 'title', 'action' => null, 'href' => null])
<div class="grid justify-items-center gap-3 px-6 py-16 text-center" data-reveal>
    <span class="grid place-items-center size-14 rounded-2xl bg-brand-soft text-brand text-2xl"><i class="ph {{ $icon }}"></i></span>
    <p class="max-w-[40ch] text-fg font-medium">{{ $title }}</p>
    @if ($action)
        <a href="{{ $href }}" class="btn btn-primary mt-2"><i class="ph ph-plus"></i>{{ $action }}</a>
    @endif
</div>
