<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | Driver default untuk hashing (password). Support: "bcrypt", "argon", "argon2id".
    |
    */
    'driver' => env('HASH_DRIVER', 'bcrypt'),

    /*
    |--------------------------------------------------------------------------
    | Bcrypt Options
    |--------------------------------------------------------------------------
    |
    | Pengaturan untuk algoritma bcrypt.
    |
    */
    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),    // cost factor
        'verify' => env('HASH_VERIFY', true),    // memverifikasi algoritma saat check
        'limit' => env('BCRYPT_LIMIT', null),    // batas panjang password (bytes)
    ],

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Pengaturan untuk algoritma argon2.
    |
    */
    'argon' => [
        'memory' => env('ARGON_MEMORY', 65536), // 64 MB
        'threads' => env('ARGON_THREADS', 1),
        'time' => env('ARGON_TIME', 4),
        'verify' => env('HASH_VERIFY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rehash On Login
    |--------------------------------------------------------------------------
    |
    | Jika true, Laravel akan otomatis rehash password saat login jika cost berubah.
    |
    */
    'rehash_on_login' => env('HASH_REHASH_ON_LOGIN', true),

];
