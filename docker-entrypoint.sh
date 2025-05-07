#!/bin/bash
set -e

# Estilo para mensagens
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # Sem cor

echo -e "${YELLOW}Inicializando o ambiente de desenvolvimento...${NC}"

# Função de tratamento de erro
error_exit() {
    echo -e "${RED}Erro: $1${NC}" >&2
    exit 1
}

# Exibir informações de configuração do banco de dados
echo -e "${YELLOW}Configuração do banco de dados:${NC}"
echo "Host: $DB_HOST"
echo "Banco: $DB_DATABASE"
echo "Usuário: $DB_USERNAME"

# Aguardar o banco de dados usando o wait
echo -e "${YELLOW}Aguardando o banco de dados ficar disponível usando wait...${NC}"
/wait

# Função para verificar se o banco de dados está pronto
check_db() {
    php -r "
    try {
        \$dsn = 'mysql:host=${DB_HOST};dbname=${DB_DATABASE};port=${DB_PORT}';
        \$pdo = new PDO(\$dsn, '${DB_USERNAME}', '${DB_PASSWORD}', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        echo 'connected';
    } catch (PDOException \$e) {
        echo \$e->getMessage();
        exit(1);
    }
    "
}

# Verificar a conexão após o wait
echo -e "${YELLOW}Verificando conexão com o banco de dados...${NC}"
if ! check_db | grep -q 'connected'; then
    error_exit "Não foi possível conectar ao banco de dados após wait"
fi

echo -e "${GREEN}Banco de dados disponível!${NC}"

# Verificar se os diretórios essenciais existem
for DIR in "runtime" "web/assets"; do
    if [ ! -d "$DIR" ]; then
        echo -e "${YELLOW}Criando diretório $DIR...${NC}"
        mkdir -p "$DIR" || error_exit "Falha ao criar diretório $DIR"
    fi
    
    # Garantir permissões corretas
    echo -e "${YELLOW}Aplicando permissões em $DIR...${NC}"
    chmod -R 775 "$DIR" || error_exit "Falha ao aplicar permissões em $DIR"
    chown -R www-data:www-data "$DIR" || error_exit "Falha ao aplicar permissões de proprietário em $DIR"
done

# Verificar arquivo de execução do Yii
if [ -f "yii" ]; then
    chmod 755 yii || error_exit "Falha ao aplicar permissão ao arquivo yii"
else
    error_exit "Arquivo yii não encontrado. Verifique a instalação."
fi

# Instalar dependências com Composer se necessário
if [ ! -d vendor ] || [ ! -f vendor/autoload.php ]; then
    echo -e "${YELLOW}Instalando dependências via Composer...${NC}"
    composer install --no-interaction --prefer-dist --optimize-autoloader || error_exit "Falha na instalação de dependências"
    echo -e "${GREEN}Dependências instaladas!${NC}"
else
    echo -e "${GREEN}Dependências já instaladas.${NC}"
fi

# Função para executar comando PHP com segurança
execute_php() {
    php -r "$1" || error_exit "Falha ao executar comando PHP: $1"
}

# Executar migrações no banco de dados
echo -e "${YELLOW}Executando migrações no banco de dados...${NC}"
php yii migrate --interactive=0 || error_exit "Falha ao executar migrações"
echo -e "${GREEN}Migrações aplicadas!${NC}"

# Verificar se já existem usuários no sistema
USER_COUNT=$(execute_php "
try {
    \$dsn = 'mysql:host=${DB_HOST};dbname=${DB_DATABASE};port=${DB_PORT}';
    \$pdo = new PDO(\$dsn, '${DB_USERNAME}', '${DB_PASSWORD}', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    \$stmt = \$pdo->query('SELECT COUNT(*) FROM user');
    echo \$stmt->fetchColumn();
} catch (PDOException \$e) {
    echo '0';
}
")

# Preparar banco de dados de teste com tratamento de erro
echo -e "${YELLOW}Preparando banco de dados de testes...${NC}"
php prepare-test-db.php || echo -e "${YELLOW}Aviso: Falha ao preparar banco de dados de teste${NC}"
echo -e "${GREEN}Banco de dados de teste preparado!${NC}"

# Inserir dados de demonstração para testes
echo -e "${YELLOW}Inserindo dados de demonstração para testes...${NC}"
php seed-test-db.php || echo -e "${YELLOW}Aviso: Falha ao inserir dados de teste${NC}"
echo -e "${GREEN}Dados de teste inseridos!${NC}"

# Inserir dados iniciais se não existirem usuários
if [ "$USER_COUNT" -eq "0" ]; then
    echo -e "${YELLOW}Criando usuário de demonstração...${NC}"
    
    # Criação do usuário demo com tratamento de erro
    execute_php "
        require_once 'vendor/autoload.php';
        require_once 'vendor/yiisoft/yii2/Yii.php';
        
        \$config = require 'config/web.php';
        new yii\\web\\Application(\$config);
        
        \$user = new app\\models\\User();
        \$user->username = 'demo';
        \$user->email = 'demo@example.com';
        \$user->setPassword('demo123');
        \$user->generateAuthKey();
        if (!\$user->save()) {
            throw new Exception('Falha ao criar usuário demo: ' . json_encode(\$user->errors));
        }
        
        echo \"Usuário demo criado com sucesso!\\n\";
    "
    
    echo -e "${YELLOW}Criando dados de exemplo para demonstração...${NC}"
    
    # Adicionar despesas de exemplo com tratamento de erro
    execute_php "
        require_once 'vendor/autoload.php';
        require_once 'vendor/yiisoft/yii2/Yii.php';
        
        \$config = require 'config/web.php';
        new yii\\web\\Application(\$config);
        
        \$user = app\\models\\User::findByUsername('demo');
        
        if (!\$user) {
            throw new Exception('Usuário demo não encontrado');
        }
        
        \$despesas = [
            ['Mercado mensal', 'alimentacao', 450.00, '2023-01-10'],
            ['Gasolina', 'transporte', 200.00, '2023-01-15'],
            ['Cinema', 'lazer', 80.00, '2023-01-20'],
            ['Conta de luz', 'moradia', 120.00, '2023-01-25'],
            ['Restaurante', 'alimentacao', 150.00, '2023-02-05'],
            ['Uber', 'transporte', 50.00, '2023-02-10'],
            ['Show', 'lazer', 200.00, '2023-02-15'],
            ['Internet', 'moradia', 100.00, '2023-02-20']
        ];
        
        \$sucessos = 0;
        foreach (\$despesas as \$d) {
            \$despesa = new app\\models\\Despesa();
            \$despesa->user_id = \$user->id;
            \$despesa->descricao = \$d[0];
            \$despesa->categoria = \$d[1];
            \$despesa->valor = \$d[2];
            \$despesa->data = \$d[3];
            if (\$despesa->save()) {
                \$sucessos++;
            }
        }
        
        echo \"Foram criadas {\$sucessos} despesas de exemplo para o usuário demo.\\n\";
    "
    
    echo -e "${GREEN}Dados de demonstração criados!${NC}"
else
    echo -e "${GREEN}Já existem usuários no sistema. Pulando a criação dos dados de demonstração.${NC}"
fi

echo -e "${GREEN}Ambiente preparado com sucesso!${NC}"

# Preparar o ambiente PHP-FPM para executar como www-data
echo -e "${YELLOW}Ajustando permissões finais...${NC}"
chown -R www-data:www-data /var/www/html
echo -e "${GREEN}Permissões ajustadas!${NC}"

# Executar o comando original (php-fpm)
exec "$@" 