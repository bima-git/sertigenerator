@extends('errors.layout')
@section('code', '404')
@section('title', 'Halaman tidak ditemukan')
@section('message', 'Alamat mungkin salah ketik atau datanya sudah tidak ada.')
@section('action')
    <a href="{{ url('/terbitkan') }}" class="btn btn-primary"><i class="ph ph-seal-check"></i>Ke Terbitkan</a>
@endsection
