<!DOCTYPE html>
<html lang="id">
<head>
    @include('layouts.head')
    <title>@yield('title') | XCODE Sertifikat</title>
</head>
<body class="min-h-dvh antialiased grid place-items-center p-4">
    <main class="card relative overflow-hidden w-full max-w-md p-8 text-center" data-reveal>
        <div class="glow w-64 h-64 -right-24 -top-28 opacity-25"></div>
        <x-logo class="relative mx-auto mb-5 w-16" animate="assemble" />
        <p class="num relative text-6xl font-semibold tracking-tighter text-brand">@yield('code')</p>
        <h1 class="relative mt-4 text-[22px] font-semibold">@yield('title')</h1>
        <p class="relative mt-2 text-muted">@yield('message')</p>
        <div class="relative mt-7">@yield('action')</div>
    </main>
</body>
</html>
