@extends('errors.layout')

@section('title', '419 - Halaman Kedaluwarsa')
@section('code', '419')
@section('message', 'Sesi Anda mungkin telah berakhir atau formula ini tidak berlaku lagi.')
@section('helper', 'Segarkan halaman untuk mencoba sekali lagi.')
@section('button_label', 'Muat Ulang')
@section('button_url', url()->current())
@section('art', 'images/error-abstract-graph.svg')
