<?php

use yii\db\Migration;

class m250118_195453_create_despesas_table extends Migration
{
    public function safeUp()
    {
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

        // Índices para otimizar consultas
        $this->createIndex('idx_despesas_user_id', 'despesas', 'user_id');
        $this->createIndex('idx_despesas_deleted_at', 'despesas', 'deleted_at');

        // Chave estrangeira para users (ON DELETE CASCADE)
        $this->addForeignKey('fk_despesas_user', 'despesas', 'user_id', 'users', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_despesas_user', 'despesas');
        $this->dropIndex('idx_despesas_user_id', 'despesas');
        $this->dropIndex('idx_despesas_deleted_at', 'despesas');
        $this->dropTable('despesas');
    }
}
