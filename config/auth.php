<?php

return [
    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'users',
        ],
    ],

    // Alle Konten liegen in der Tabelle users. Eigene Konten haben ein
    // Passwort; Konten aus dem Verzeichnis (auth_source = ldap) werden beim
    // Anmelden per LDAP-Bind geprüft (App\Support\Directory, Standard 09).
    // All accounts live in the users table. Own accounts have a
    // password; accounts from the directory (auth_source = ldap) are checked
    // on sign-in via LDAP bind (App\Support\Directory, Standard 09).
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model'  => App\Models\User::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
