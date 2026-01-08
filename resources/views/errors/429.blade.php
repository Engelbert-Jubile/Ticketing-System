@extends('errors.layout')

@section('title', '429 - Terlalu Banyak Permintaan')
@section('code', '429')
@section('message', 'Kami menerima terlalu banyak permintaan dalam waktu singkat.')
@section('helper', 'Tunggu beberapa saat lalu coba kembali.')
@section('button_label', 'Kembali ke Beranda')
@section('button_url', route('dashboard'))
@section('art', 'images/error-abstract-graph.svg')
