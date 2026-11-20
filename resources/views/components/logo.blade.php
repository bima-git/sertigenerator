{{--
    Logo XCODE {><} sebagai SVG agar tajam di semua ukuran dan tiap bagian bisa dianimasikan.
    variant: mark (hanya {><}) | full ({><} + XCODE) | stacked ({><} di atas XCODE)
    tone: dark (kurung hitam, untuk latar terang) | light (kurung putih, untuk latar gelap)
    animate: assemble (kurung dan panah menyatu saat tampil) | breathe (panah berdenyut) | none
--}}
@props(['variant' => 'mark', 'tone' => 'dark', 'animate' => 'none'])
@php
    $brace = $tone === 'light' ? 'var(--logo-light)' : 'var(--logo-dark)';
    $w = ['mark' => 120, 'full' => 300, 'stacked' => 160][$variant];
    $h = $variant === 'stacked' ? 112 : 64;
    $dx = $variant === 'stacked' ? 20 : 0;
@endphp
<svg {{ $attributes->merge(['class' => "logo logo-{$animate}"]) }} viewBox="0 0 {{ $w }} {{ $h }}" role="img" aria-label="XCODE" xmlns="http://www.w3.org/2000/svg">
    <g transform="translate({{ $dx }} 0)">
        <path class="logo-brace-l" d="M22 6C14 6 12 9.5 12 16v8c0 5-2 7-7 8c5 1 7 3 7 8v8c0 6.5 2 10 10 10" fill="none" stroke="{{ $brace }}" stroke-width="8.5"/>
        <path class="logo-brace-r" d="M98 6c8 0 10 3.5 10 10v8c0 5 2 7 7 8c-5 1-7 3-7 8v8c0 6.5-2 10-10 10" fill="none" stroke="{{ $brace }}" stroke-width="8.5"/>
        <polygon class="logo-chev-l" points="27,15 56,28.5 56,35.5 27,49 27,40 45,32 27,24" fill="var(--logo-red)"/>
        <polygon class="logo-chev-r" points="93,15 64,28.5 64,35.5 93,49 93,40 75,32 93,24" fill="var(--logo-red)"/>
    </g>
    @if ($variant === 'full')
        <text x="126" y="50" class="logo-word" fill="{{ $brace }}" font-family="Geist Variable, sans-serif" font-weight="900" font-size="50" letter-spacing="-2">XCODE</text>
    @elseif ($variant === 'stacked')
        <text x="80" y="106" text-anchor="middle" class="logo-word" fill="{{ $brace }}" font-family="Geist Variable, sans-serif" font-weight="900" font-size="40" letter-spacing="-1.5">XCODE</text>
    @endif
</svg>
