{{-- Aset bersama: font di-preload agar teks dan ikon tidak muncul belakangan (kedip). --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
@foreach ([
    'node_modules/@fontsource-variable/geist/files/geist-latin-wght-normal.woff2',
    'node_modules/@fontsource-variable/geist-mono/files/geist-mono-latin-wght-normal.woff2',
    'node_modules/@phosphor-icons/web/src/regular/Phosphor.woff2',
    'node_modules/@phosphor-icons/web/src/fill/Phosphor-Fill.woff2',
] as $font)
    <link rel="preload" href="{{ Vite::asset($font) }}" as="font" type="font/woff2" crossorigin>
@endforeach
@vite(['resources/css/app.css', 'resources/js/app.js'])
