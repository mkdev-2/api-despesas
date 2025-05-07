<?php

use yii\db\Migration;

class m250118_201422_optimize_database extends Migration
{
    public function safeUp()
    {
        // Verifica se a tabela despesas existe
        if ($this->db->schema->getTableSchema('despesas') === null) {
            echo "Tabela despesas não existe, pulando otimização.\n";
            return true;
        }

        // Verifica se os índices já existem
        $indexCategoriaExists = $this->db->createCommand("SHOW INDEX FROM despesas WHERE Key_name = 'idx_despesas_categoria'")->queryOne() !== false;
        if (!$indexCategoriaExists) {
            // Índice para categoria
            $this->createIndex('idx_despesas_categoria', 'despesas', ['categoria(20)']);
        }

        $indexDataExists = $this->db->createCommand("SHOW INDEX FROM despesas WHERE Key_name = 'idx_despesas_data'")->queryOne() !== false;
        if (!$indexDataExists) {
            // Índice para data
            $this->createIndex('idx_despesas_data', 'despesas', 'data');
        }
    }

    public function safeDown()
    {
        // Verifica se a tabela despesas existe
        if ($this->db->schema->getTableSchema('despesas') === null) {
            echo "Tabela despesas não existe, pulando remoção de índices.\n";
            return true;
        }

        // Verifica se os índices existem antes de tentar removê-los
        $indexCategoriaExists = $this->db->createCommand("SHOW INDEX FROM despesas WHERE Key_name = 'idx_despesas_categoria'")->queryOne() !== false;
        if ($indexCategoriaExists) {
            $this->dropIndex('idx_despesas_categoria', 'despesas');
        }

        $indexDataExists = $this->db->createCommand("SHOW INDEX FROM despesas WHERE Key_name = 'idx_despesas_data'")->queryOne() !== false;
        if ($indexDataExists) {
            $this->dropIndex('idx_despesas_data', 'despesas');
        }
    }
}
