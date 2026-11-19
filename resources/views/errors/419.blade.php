@extends('errors.layout')
@section('code', '419')
@section('title', 'Halaman kedaluwarsa')
@section('message', 'Sesi form berakhir karena terlalu lama terbuka. Muat ulang lalu kirim lagi.')
@section('action')
    <button type="button" onclick="location.reload()" class="btn btn-primary"><i class="ph ph-arrow-clockwise"></i>Muat Ulang</button>
@endsection
