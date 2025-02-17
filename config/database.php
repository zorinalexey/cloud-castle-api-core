<?php

return [
    'default' => [
        'db_type' => 'pgsql',
        'connect_params' => [
            'dsn' => 'pgsql:host=' . env('DEFAULT_DATABASE_HOST', 'localhost') .
                ';port=' . env('DEFAULT_DATABASE_PORT', 5432) .
                ';dbname=' . env('DEFAULT_DATABASE_NAME'),
            'user' => env('DEFAULT_DATABASE_USERNAME', 'root'),
            'password' => env('DEFAULT_DATABASE_PASSWORD', 'root'),
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            ]
        ]
    ],
];