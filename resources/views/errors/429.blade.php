@extends('errors.layout')
@section('code', '429')
@section('title', 'Terlalu banyak permintaan')
@section('message', 'Tunggu sebentar sebelum mencoba lagi.')
@section('action')
    <button type="button" onclick="history.back()" class="btn btn-secondary"><i class="ph ph-arrow-left"></i>Kembali</button>
@endsection
