@extends('errors.layout')

@section('title', '500 - Kesalahan Server')
@section('code', '500')
@section('message', 'Kami mengalami masalah di sisi server. Silakan coba lagi nanti.')
@section('helper', 'Tim teknis sedang menangani gangguan ini.')
@section('button_label', 'Kembali ke Dasbor')
@section('button_url', route('dashboard'))
@section('art', 'images/error-abstract-graph.svg')
