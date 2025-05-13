#!/bin/bash
set -e

echo "Inicializando o banco de dados para desenvolvimento e testes..."

# Cria o banco de dados principal se não existir
echo "Criando banco de dados principal..."
mysql -h despesas_db -u root -ppassword -e "CREATE DATABASE IF NOT EXISTS gerenciamento_despesas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Cria o banco de dados de teste se não existir
echo "Criando banco de dados de teste..."
mysql -h despesas_db -u root -ppassword -e "CREATE DATABASE IF NOT EXISTS gerenciamento_despesas_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Criar tabela de migrações
echo "Criando tabela de migrações..."
mysql -h despesas_db -u root -ppassword gerenciamento_despesas -e "
CREATE TABLE IF NOT EXISTS migration (
    version varchar(180) NOT NULL,
    apply_time int(11) NULL,
    PRIMARY KEY (version)
);"

# Marcar migrações iniciais como concluídas
echo "Marcando migrações iniciais como concluídas..."
mysql -h despesas_db -u root -ppassword gerenciamento_despesas -e "
INSERT IGNORE INTO migration (version, apply_time) VALUES
('m000000_000000_base', UNIX_TIMESTAMP()),
('m230101_000001_create_despesas_table', UNIX_TIMESTAMP()),
('m250118_194946_create_users_table', UNIX_TIMESTAMP()),
('m250118_195453_create_despesas_table', UNIX_TIMESTAMP()),
('m250118_201422_optimize_database', UNIX_TIMESTAMP());"

# Verificar se a tabela de usuários existe, senão criar
echo "Verificando e criando tabela de usuários se necessário..."
TABLE_EXISTS=$(mysql -h despesas_db -u root -ppassword -se "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'gerenciamento_despesas' AND table_name = 'users'")

if [ "$TABLE_EXISTS" -eq "0" ]; then
    echo "Criando tabela de usuários..."
    mysql -h despesas_db -u root -ppassword gerenciamento_despesas -e "
    CREATE TABLE IF NOT EXISTS users (
      id int(11) NOT NULL AUTO_INCREMENT,
      username varchar(50) NOT NULL,
      email varchar(255) NOT NULL,
      auth_key varchar(32) DEFAULT NULL,
      password_hash varchar(255) NOT NULL,
      password_reset_token varchar(255) DEFAULT NULL,
      status tinyint(1) NOT NULL DEFAULT 10,
      created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      deleted_at timestamp NULL DEFAULT NULL,
      PRIMARY KEY (id),
      UNIQUE KEY username (username),
      UNIQUE KEY email (email),
      UNIQUE KEY password_reset_token (password_reset_token)
    );"
else
    echo "Tabela users já existe."
fi

# Verificar se a tabela de despesas existe, senão criar
echo "Verificando e criando tabela de despesas se necessário..."
TABLE_EXISTS=$(mysql -h despesas_db -u root -ppassword -se "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'gerenciamento_despesas' AND table_name = 'despesas'")

if [ "$TABLE_EXISTS" -eq "0" ]; then
    echo "Criando tabela de despesas..."
    mysql -h despesas_db -u root -ppassword gerenciamento_despesas -e "
    CREATE TABLE IF NOT EXISTS despesas (
      id int(11) NOT NULL AUTO_INCREMENT,
      user_id int(11) NOT NULL,
      descricao varchar(255) NOT NULL,
      categoria varchar(50) NOT NULL,
      valor decimal(10,2) NOT NULL,
      data date NOT NULL,
      created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      deleted_at timestamp NULL DEFAULT NULL,
      PRIMARY KEY (id),
      KEY idx_despesas_user_id (user_id),
      KEY idx_despesas_categoria (categoria),
      KEY idx_despesas_data (data),
      KEY idx_despesas_deleted_at (deleted_at),
      CONSTRAINT fk_despesas_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
    );"
else
    echo "Tabela despesas já existe."
fi

# Verificar se o usuário demo existe, senão criar
echo "Verificando e criando usuário de demonstração se necessário..."
USER_EXISTS=$(mysql -h despesas_db -u root -ppassword -se "SELECT COUNT(*) FROM gerenciamento_despesas.users WHERE username = 'demo'")

if [ "$USER_EXISTS" -eq "0" ]; then
    echo "Criando usuário de demonstração..."
    PASSWORD_HASH=$(php -r 'echo password_hash("demo123", PASSWORD_BCRYPT, ["cost" => 13]);')
    mysql -h despesas_db -u root -ppassword gerenciamento_despesas -e "
    INSERT INTO users (username, email, auth_key, password_hash, status, created_at, updated_at)
    VALUES ('demo', 'demo@example.com', 'test-auth-key', '$PASSWORD_HASH', 10, NOW(), NOW());"
else
    echo "Usuário demo já existe."
fi

# Garantir permissões corretas para os bancos de dados
echo "Configurando permissões do banco de dados..."
mysql -h despesas_db -u root -ppassword -e "
GRANT ALL PRIVILEGES ON gerenciamento_despesas.* TO 'despesas'@'%';
GRANT ALL PRIVILEGES ON gerenciamento_despesas_test.* TO 'despesas'@'%';
FLUSH PRIVILEGES;"

# Inicializar banco de dados de teste
echo "Inicializando banco de dados de teste..."
mysql -h despesas_db -u root -ppassword -e "
DROP DATABASE IF EXISTS gerenciamento_despesas_test;
CREATE DATABASE gerenciamento_despesas_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gerenciamento_despesas_test;

CREATE TABLE IF NOT EXISTS users (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(50) NOT NULL,
  email varchar(255) NOT NULL,
  auth_key varchar(32) DEFAULT NULL,
  password_hash varchar(255) NOT NULL,
  password_reset_token varchar(255) DEFAULT NULL,
  status tinyint(1) NOT NULL DEFAULT 10,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY username (username),
  UNIQUE KEY email (email),
  UNIQUE KEY password_reset_token (password_reset_token)
);

CREATE TABLE IF NOT EXISTS despesas (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  descricao varchar(255) NOT NULL,
  categoria varchar(50) NOT NULL,
  valor decimal(10,2) NOT NULL,
  data date NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at timestamp NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_despesas_user_id (user_id),
  KEY idx_despesas_categoria (categoria),
  KEY idx_despesas_data (data),
  KEY idx_despesas_deleted_at (deleted_at),
  CONSTRAINT fk_despesas_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS migration (
    version varchar(180) NOT NULL,
    apply_time int(11) NULL,
    PRIMARY KEY (version)
);

INSERT INTO migration (version, apply_time) VALUES
('m000000_000000_base', UNIX_TIMESTAMP()),
('m230101_000001_create_despesas_table', UNIX_TIMESTAMP()),
('m250118_194946_create_users_table', UNIX_TIMESTAMP()),
('m250118_195453_create_despesas_table', UNIX_TIMESTAMP()),
('m250118_201422_optimize_database', UNIX_TIMESTAMP());

INSERT INTO users (id, username, email, auth_key, password_hash, status, created_at, updated_at)
VALUES (1, 'test', 'test@example.com', 'test-auth-key', 
'$PASSWORD_HASH', 
10, NOW(), NOW());"

echo "Inicialização do banco de dados concluída com sucesso!" 