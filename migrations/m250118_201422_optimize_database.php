<?php

use yii\db\Migration;

class m250118_201422_optimize_database extends Migration
{
    public function safeUp()
    {
        // Índices para otimizar buscas
        $this->createIndex('idx_despesas_categoria', 'despesas', ['categoria(20)']);
        $this->createIndex('idx_despesas_data', 'despesas', 'data');
    }

    public function safeDown()
    {
        $this->dropIndex('idx_despesas_categoria', 'despesas');
        $this->dropIndex('idx_despesas_data', 'despesas');
    }
}
