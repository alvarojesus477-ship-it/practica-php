<?php

// Archivo de configuración. Se debe sacar del control de versiones (en esta práctica puedes dejarlo)
// Suele llamarse config.php o .env, pero en realidad puedes ponerle cualquier otro nombre.
// Los valores de estas variables deben coincidir con los de tu docker-compose.yml.
return [
    'db' => [
        'host'    => 'db',
        'dbname'  => 'tablon_nuncios',
        'user'    => 'root',
        'pass'    => 'laquesea',
        'charset' => 'utf8mb4',
    ],
];