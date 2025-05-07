-- Configuração de segurança para inicialização do banco
SET GLOBAL sql_mode = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';

-- Criar banco de dados principal (caso não exista)
CREATE DATABASE IF NOT EXISTS gerenciamento_despesas 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Criar banco de dados de teste
CREATE DATABASE IF NOT EXISTS gerenciamento_despesas_test 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Conceder privilégios mínimos necessários ao usuário (princípio do privilégio mínimo)
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, REFERENCES, TRIGGER 
ON gerenciamento_despesas.* TO 'user'@'%';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, REFERENCES, TRIGGER 
ON gerenciamento_despesas_test.* TO 'user'@'%';

-- Aplicar as mudanças de privilégios
FLUSH PRIVILEGES;

-- Garantir que o usuário não tenha acesso a outros bancos
REVOKE ALL PRIVILEGES ON *.* FROM 'user'@'%';
REVOKE GRANT OPTION ON *.* FROM 'user'@'%';

-- Restaurar os privilégios específicos apenas para os bancos necessários
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, REFERENCES, TRIGGER 
ON gerenciamento_despesas.* TO 'user'@'%';

GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, REFERENCES, TRIGGER 
ON gerenciamento_despesas_test.* TO 'user'@'%';

-- Aplicar novamente as mudanças de privilégios
FLUSH PRIVILEGES; 