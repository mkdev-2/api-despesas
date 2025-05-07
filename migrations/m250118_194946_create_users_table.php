<?php

use yii\db\Migration;

class m250118_194946_create_users_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('users', [
            'id' => $this->primaryKey(),
            'username' => $this->string(50)->notNull()->unique(), // ✅ Adicionado
            'email' => $this->string(255)->notNull()->unique(),
            'password_hash' => $this->string(255)->notNull(),
            'access_token' => $this->string(255)->defaultValue(null), // 🔹 Pode ser NULL
            'auth_key' => $this->string(32)->defaultValue(null), // ✅ Adicionado para autenticação
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->notNull(),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP')->append('ON UPDATE CURRENT_TIMESTAMP')->notNull(),
            'deleted_at' => $this->timestamp()->null()->defaultValue(null),
        ]);

        $this->createIndex('idx_users_email', 'users', 'email');
        $this->createIndex('idx_users_username', 'users', 'username'); // ✅ Adicionado
        $this->createIndex('idx_users_deleted_at', 'users', 'deleted_at');
    }

    public function safeDown()
    {
        $this->dropTable('users');
    }
}
