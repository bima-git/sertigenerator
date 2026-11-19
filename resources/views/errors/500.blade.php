@extends('errors.layout')
@section('code', '500')
@section('title', 'Terjadi kesalahan')
@section('message', 'Sistem belum bisa memproses permintaan ini. Coba lagi, bila berulang hubungi admin.')
@section('action')
    <a href="{{ url('/terbitkan') }}" class="btn btn-primary"><i class="ph ph-arrow-clockwise"></i>Coba Lagi</a>
@endsection
