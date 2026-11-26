@extends('errors.layout')
@section('code', '500')
@section('title', 'Terjadi kesalahan')
@section('message')
    Sistem belum bisa memproses permintaan ini. Coba lagi, bila berulang hubungi admin.
    @if ($ref = \Illuminate\Support\Facades\Context::get('ref'))
        <span class="mt-3 block">Kode rujukan: <span class="num font-semibold text-fg">{{ $ref }}</span></span>
    @endif
@endsection
@section('action')
    <a href="{{ url('/terbitkan') }}" class="btn btn-primary"><i class="ph ph-arrow-clockwise"></i>Coba Lagi</a>
@endsection
