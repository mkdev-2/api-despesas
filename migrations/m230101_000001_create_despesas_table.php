<?php

use yii\db\Migration;

/**
 * Class m230101_000001_create_despesas_table
 * 
 * Migração para criar a tabela de despesas conforme requisitos do projeto
 */
class m230101_000001_create_despesas_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%despesas}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'descricao' => $this->string(255)->notNull()->comment('Descrição da despesa'),
            'categoria' => $this->string(50)->notNull()->comment('Categoria (alimentação, transporte, lazer, etc)'),
            'valor' => $this->decimal(10, 2)->notNull()->comment('Valor da despesa'),
            'data' => $this->date()->notNull()->comment('Data da despesa'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
            'deleted_at' => $this->timestamp()->null()->comment('Data de exclusão lógica'),
        ]);

        // Adiciona índices para melhorar a performance de consultas
        $this->createIndex('idx-despesas-user_id', '{{%despesas}}', 'user_id');
        $this->createIndex('idx-despesas-categoria', '{{%despesas}}', 'categoria');
        $this->createIndex('idx-despesas-data', '{{%despesas}}', 'data');
        $this->createIndex('idx-despesas-deleted_at', '{{%despesas}}', 'deleted_at');

        // Adiciona chave estrangeira para a tabela de usuários
        $this->addForeignKey(
            'fk-despesas-user_id',
            '{{%despesas}}',
            'user_id',
            '{{%user}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-despesas-user_id', '{{%despesas}}');
        $this->dropTable('{{%despesas}}');
    }
} 