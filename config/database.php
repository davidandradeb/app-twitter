<?php

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST'),
            'port' => env( 'DB_PORT', '3306' ),
            'database' => env('DB_DATABASE'),
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'unix_socket' => env( 'DB_SOCKET',''),
            'charset' => 'utf8',
            'collation' => 'utf8_bin',
        ],
        
    ],
];
