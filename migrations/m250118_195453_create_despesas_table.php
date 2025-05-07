<?php

use yii\db\Migration;

class m250118_195453_create_despesas_table extends Migration
{
    public function safeUp()
    {
        // Verifica se a tabela já existe (pode ter sido criada pela migração anterior)
        $tableExists = $this->db->schema->getTableSchema('despesas') !== null;
        
        if (!$tableExists) {
            $this->createTable('despesas', [
                'id' => $this->primaryKey(),
                'user_id' => $this->integer()->notNull(),
                'descricao' => $this->string(255)->notNull(),
                'categoria' => $this->string(100)->notNull(),
                'valor' => $this->decimal(10,2)->notNull(),
                'data' => $this->date()->notNull(),
                'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->notNull(),
                'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP')->notNull(),
                'deleted_at' => $this->timestamp()->null()->defaultValue(null), // Soft delete
            ]);
        }

        // Verifica se os índices já existem
        $indexExists = $this->db->createCommand("SHOW INDEX FROM despesas WHERE Key_name = 'idx_despesas_user_id'")->queryOne() !== false;
        if (!$indexExists) {
            // Índices para otimizar consultas
            $this->createIndex('idx_despesas_user_id', 'despesas', 'user_id');
            $this->createIndex('idx_despesas_deleted_at', 'despesas', 'deleted_at');
        }

        // Verifica se a chave estrangeira já existe
        $fkExists = $this->db->createCommand("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_despesas_user'")->queryOne() !== false;
        if (!$fkExists) {
            // Verifica se a tabela users existe
            $usersTableExists = $this->db->schema->getTableSchema('users') !== null;
            if ($usersTableExists) {
                // Adiciona a chave estrangeira apenas se a tabela users existir
                $this->addForeignKey('fk_despesas_user', 'despesas', 'user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            }
        }
    }

    public function safeDown()
    {
        // Verifica se a chave estrangeira existe antes de tentar removê-la
        $fkExists = $this->db->createCommand("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_despesas_user'")->queryOne() !== false;
        if ($fkExists) {
            $this->dropForeignKey('fk_despesas_user', 'despesas');
        }

        // Verifica se os índices existem antes de tentar removê-los
        $indexUserIdExists = $this->db->createCommand("SHOW INDEX FROM despesas WHERE Key_name = 'idx_despesas_user_id'")->queryOne() !== false;
        if ($indexUserIdExists) {
            $this->dropIndex('idx_despesas_user_id', 'despesas');
        }

        $indexDeletedAtExists = $this->db->createCommand("SHOW INDEX FROM despesas WHERE Key_name = 'idx_despesas_deleted_at'")->queryOne() !== false;
        if ($indexDeletedAtExists) {
            $this->dropIndex('idx_despesas_deleted_at', 'despesas');
        }

        // Não removemos a tabela despesas aqui, pois ela pode ter sido criada por outra migração
    }
}
