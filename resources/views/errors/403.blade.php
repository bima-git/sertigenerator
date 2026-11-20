@extends('errors.layout')
@section('code', '403')
@section('title', 'Akses ditolak')
@section('message', 'Halaman ini hanya untuk admin.')
@section('action')
    <a href="{{ url('/terbitkan') }}" class="btn btn-primary"><i class="ph ph-seal-check"></i>Ke Terbitkan</a>
@endsection
