@extends('errors.layout')

@section('title', '404 - Halaman Tidak Ditemukan')
@section('code', '404')
@section('message', 'Halaman yang Anda cari tidak tersedia. Periksa kembali tautan atau kembali ke dasbor untuk mulai lagi.')

@section('helper', 'Oops! Halaman tidak ditemukan')
@section('button_label', 'Kembali ke Dashboard')
@section('button_url', route('dashboard'))
@section('art', 'images/page-not-found-404.svg')
