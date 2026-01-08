@extends('errors.layout')

@section('title', '402 - Pembayaran Diperlukan')
@section('code', '402')
@section('message', 'Pembayaran diperlukan untuk melanjutkan akses ini.')
@section('helper', 'Perbarui paket Anda atau hubungi admin agar terus bisa masuk.')
@section('button_label', 'Kembali ke Dasbor')
@section('button_url', route('dashboard'))
@section('art', 'images/error-abstract-graph.svg')
