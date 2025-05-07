<?php

return [
    'despesa1' => [
        'id' => 1,
        'user_id' => 1, // Usuário "admin" do fixture UserFixture
        'descricao' => 'Almoço de negócios',
        'categoria' => 'alimentacao',
        'valor' => 58.90,
        'data' => date('Y-m-d', strtotime('-1 day')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'deleted_at' => null,
    ],
    'despesa2' => [
        'id' => 2,
        'user_id' => 1, // Usuário "admin" do fixture UserFixture
        'descricao' => 'Táxi para reunião',
        'categoria' => 'transporte',
        'valor' => 35.50,
        'data' => date('Y-m-d', strtotime('-2 days')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'deleted_at' => null,
    ],
    'despesa3' => [
        'id' => 3,
        'user_id' => 1, // Usuário "admin" do fixture UserFixture
        'descricao' => 'Cinema com cliente',
        'categoria' => 'lazer',
        'valor' => 45.00,
        'data' => date('Y-m-d', strtotime('-3 days')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'deleted_at' => null,
    ],
    'despesa4' => [
        'id' => 4,
        'user_id' => 2, // Usuário "usuario.teste" do fixture UserFixture
        'descricao' => 'Mercado',
        'categoria' => 'alimentacao',
        'valor' => 120.75,
        'data' => date('Y-m-d', strtotime('-1 day')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'deleted_at' => null,
    ],
    'despesa5' => [
        'id' => 5,
        'user_id' => 2, // Usuário "usuario.teste" do fixture UserFixture
        'descricao' => 'Uber',
        'categoria' => 'transporte',
        'valor' => 28.90,
        'data' => date('Y-m-d', strtotime('-2 days')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'deleted_at' => null,
    ],
    'despesa_deletada' => [
        'id' => 6,
        'user_id' => 1, // Usuário "admin" do fixture UserFixture
        'descricao' => 'Despesa excluída',
        'categoria' => 'alimentacao',
        'valor' => 42.30,
        'data' => date('Y-m-d', strtotime('-5 days')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
        'deleted_at' => date('Y-m-d H:i:s', strtotime('-4 days')), // Esta despesa foi soft-deleted
    ],
]; 