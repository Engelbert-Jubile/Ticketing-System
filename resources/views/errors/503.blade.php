@extends('errors.layout')

@section('title', '503 - Layanan Tidak Tersedia')
@section('code', '503')
@section('message', 'Layanan sedang dalam perawatan atau tidak bisa diakses untuk sementara.')
@section('helper', 'Silakan coba lagi dalam beberapa menit.')
@section('button_label', 'Kembali ke Dasbor')
@section('button_url', route('dashboard'))
@section('art', 'images/error-abstract-graph.svg')
