<?php
// Define a senha hash para os usuários de teste
$password_hash = '$2y$13$F8oA1DnpOKY0zWB4W.RZXevrZr4Cvw4jc0t9/lg5fvK8R9aNbJ5rm'; // senha: admin

// Formato de data compatível com MySQL TIMESTAMP
$current_datetime = date('Y-m-d H:i:s');

return [
    'admin' => [
        'id' => 1,
        'username' => 'admin',
        'auth_key' => 'test100key',
        'password_hash' => $password_hash,
        'email' => 'admin@example.com',
        'created_at' => $current_datetime,
        'updated_at' => $current_datetime,
        'deleted_at' => null,
    ],
    'demo' => [
        'id' => 2,
        'username' => 'demo',
        'auth_key' => 'test101key',
        'password_hash' => $password_hash,
        'email' => 'demo@example.com',
        'created_at' => $current_datetime,
        'updated_at' => $current_datetime,
        'deleted_at' => null,
    ],
    'test' => [
        'id' => 3,
        'username' => 'test',
        'auth_key' => 'test102key',
        'password_hash' => $password_hash,
        'email' => 'test@example.com',
        'created_at' => $current_datetime,
        'updated_at' => $current_datetime,
        'deleted_at' => null,
    ],
    'user' => [
        'id' => 4,
        'username' => 'user',
        'auth_key' => 'test103key',
        'password_hash' => $password_hash,
        'email' => 'user@example.com',
        'created_at' => $current_datetime,
        'updated_at' => $current_datetime,
        'deleted_at' => null,
    ],
];
