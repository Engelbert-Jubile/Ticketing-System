@extends('layouts.app')

@section('title', 'Verifikasi Email')

@section('content')
<div class="flex items-center justify-center min-h-screen bg-gray-100 dark:bg-gray-900 px-4">
    <div class="w-full max-w-md">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-lg dark:border-gray-700 dark:bg-gray-800">
            <div class="text-center mb-6">
                <div class="flex justify-center mb-4">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-8 w-8">
                            <path d="M19.5 13.857a6 6 0 0 0 .75-11.857H7.5a5.25 5.25 0 0 0 1.041 10.442h.375a2.25 2.25 0 0 1 0 4.5H6.75a6 6 0 1 0 12-6.142Z" />
                        </svg>
                    </div>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Verifikasi Email</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Silakan verifikasi email Anda untuk melanjutkan</p>
            </div>

            @if (session('status') == 'verification-link-sent')
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-700/50 dark:bg-green-900/20 dark:text-green-200">
                Link verifikasi telah dikirim ke email Anda. Silakan cek inbox Anda.
            </div>
            @endif

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 mb-6 text-sm text-amber-800 dark:border-amber-700/50 dark:bg-amber-900/20 dark:text-amber-200">
                <p class="font-semibold mb-2">Instruksi Verifikasi:</p>
                <ol class="list-decimal list-inside space-y-1">
                    <li>Cek email Anda ({{ auth()->user()->email }})</li>
                    <li>Klik link verifikasi dalam email</li>
                    <li>Anda akan diarahkan ke dashboard setelah verifikasi berhasil</li>
                </ol>
            </div>

            <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
                @csrf
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Belum menerima email? Klik tombol di bawah untuk mengirim ulang link verifikasi.
                </p>
                <button type="submit" class="w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-300 dark:focus:ring-blue-800">
                    Kirim Ulang Link Verifikasi
                </button>
            </form>

            <div class="mt-6 border-t border-gray-200 pt-6 dark:border-gray-700">
                <form method="POST" action="{{ route('logout') }}" class="text-center">
                    @csrf
                    <button type="submit" class="text-sm text-blue-600 hover:underline dark:text-blue-400">
                        Logout
                    </button>
                </form>
            </div>
        </div>

        <p class="mt-6 text-center text-xs text-gray-500 dark:text-gray-400">
            {{ auth()->user()->email }}
        </p>
    </div>
</div>
@endsection
