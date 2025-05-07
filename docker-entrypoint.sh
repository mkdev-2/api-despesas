#!/bin/bash
set -e

# Função para exibir mensagens coloridas
function echo_color() {
    COLOR=$1
    MESSAGE=$2
    RESET='\033[0m'
    echo -e "${COLOR}${MESSAGE}${RESET}"
}

# Cores para mensagens
INFO='\033[0;34m'  # Azul
SUCCESS='\033[0;32m'  # Verde
WARNING='\033[0;33m'  # Amarelo
ERROR='\033[0;31m'  # Vermelho

echo_color $INFO "Inicializando o ambiente de desenvolvimento..."

# Verificar se os arquivos de configuração existem
if [ ! -f /var/www/html/config/db.php ]; then
    echo_color $ERROR "Arquivo de configuração do banco de dados não encontrado!"
    exit 1
fi

# Obter variáveis de ambiente ou definir valores padrão
DB_HOST=${DB_HOST:-despesas_db}
DB_DATABASE=${DB_DATABASE:-gerenciamento_despesas}
DB_USERNAME=${DB_USERNAME:-despesas}
DB_PASSWORD=${DB_PASSWORD:-root}
DB_PORT=${DB_PORT:-3306}

# Função para lidar com erros
function handle_error() {
    echo_color $ERROR "Erro: $1"
    exit 1
}

# Exibir informações de configuração do banco de dados
function show_db_config() {
    echo_color $INFO "Configuração do banco de dados:"
    echo "DB_HOST: $DB_HOST"
    echo "DB_PORT: $DB_PORT"
    echo "DB_DATABASE: $DB_DATABASE"
    echo "DB_DATABASE_TEST: $DB_DATABASE_TEST"
    echo "DB_USERNAME: $DB_USERNAME"
}

# Aguardar o banco de dados ficar disponível
show_db_config
echo_color $INFO "Aguardando o banco de dados ficar disponível..."

# Esperar o banco de dados ficar disponível
max_tries=30
tries=0
while [ $tries -lt $max_tries ]; do
    if mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u root -ppassword --silent; then
        echo_color $SUCCESS "Banco de dados disponível!"
        break
    fi
    tries=$((tries+1))
    if [ $tries -lt $max_tries ]; then
        echo_color $WARNING "Tentativa $tries/$max_tries: Banco de dados ainda não está pronto. Aguardando 5 segundos..."
        sleep 5
    else
        handle_error "Não foi possível conectar ao banco de dados após $max_tries tentativas."
    fi
done

# Verificar se o arquivo .env existe
if [ -f .env ]; then
    echo_color $INFO "Arquivo .env já existe."
else
    echo_color $WARNING "Arquivo .env não encontrado. Criando..."
    cp .env.example .env
    echo_color $SUCCESS "Arquivo .env criado com sucesso!"
fi

# Aplicar permissões nos diretórios necessários
echo_color $INFO "Aplicando permissões em runtime..."
mkdir -p runtime
chmod -R 777 runtime

echo_color $INFO "Aplicando permissões em web/assets..."
mkdir -p web/assets
chmod -R 777 web/assets

# Instalar dependências do Composer, se necessário
if [ -f vendor/autoload.php ]; then
    echo_color $INFO "Dependências já instaladas."
else
    echo_color $INFO "Instalando dependências..."
    composer install --no-interaction
    echo_color $SUCCESS "Dependências instaladas com sucesso!"
fi

# Executar inicialização do banco de dados se o script existir
if [ -f scripts/init-db.sh ]; then
    echo_color $INFO "Executando inicialização do banco de dados..."
    bash scripts/init-db.sh || echo_color $WARNING "Aviso: Falha ao executar a inicialização do banco de dados."
else
    echo_color $WARNING "Script de inicialização do banco de dados não encontrado (scripts/init-db.sh)."
fi

# Marcar migrações existentes
echo_color $INFO "Verificando e marcando migrações existentes..."
if [ -f scripts/mark-migrations.php ]; then
    DOCKER_ENV=1 php scripts/mark-migrations.php || echo_color $WARNING "Aviso: Falha ao marcar migrações existentes"
else
    echo_color $WARNING "Script mark-migrations.php não encontrado. Pulando."
fi

# Executar migrações do banco de dados
echo_color $INFO "Executando migrações no banco de dados..."
php yii migrate --interactive=0 || echo_color $WARNING "Aviso: Falha ao executar migrações, continuando mesmo assim..."

# Preparar banco de dados de testes
echo_color $INFO "Preparando banco de dados de testes..."
if [ -f scripts/prepare-test-db.php ]; then
    DOCKER_ENV=1 php scripts/prepare-test-db.php || echo_color $WARNING "Aviso: Falha ao preparar banco de dados de teste"
else
    echo_color $WARNING "Arquivo scripts/prepare-test-db.php não encontrado."
fi

# Conceder privilégios adicionais ao usuário do banco de dados (para resolver problemas de acesso)
echo_color $INFO "Verificando privilégios do usuário do banco de dados..."
mysql -h$DB_HOST -P$DB_PORT -uroot -ppassword -e "GRANT ALL PRIVILEGES ON $DB_DATABASE.* TO '$DB_USERNAME'@'%';" || echo_color $WARNING "Aviso: Não foi possível conceder privilégios"
mysql -h$DB_HOST -P$DB_PORT -uroot -ppassword -e "GRANT ALL PRIVILEGES ON ${DB_DATABASE_TEST}.* TO '$DB_USERNAME'@'%';" || echo_color $WARNING "Aviso: Não foi possível conceder privilégios"
mysql -h$DB_HOST -P$DB_PORT -uroot -ppassword -e "FLUSH PRIVILEGES;" || echo_color $WARNING "Aviso: Não foi possível atualizar privilégios"

# Ajustar permissões finais
echo_color $INFO "Ajustando permissões finais..."
find /var/www/html -type d -exec chmod 755 {} \; 2>/dev/null || echo_color $WARNING "Aviso: Não foi possível ajustar permissões para diretórios"
find /var/www/html -type f -exec chmod 644 {} \; 2>/dev/null || echo_color $WARNING "Aviso: Não foi possível ajustar permissões para arquivos"
chmod -R 777 /var/www/html/runtime 2>/dev/null || echo_color $WARNING "Aviso: Não foi possível ajustar permissões para runtime"
chmod -R 777 /var/www/html/web/assets 2>/dev/null || echo_color $WARNING "Aviso: Não foi possível ajustar permissões para web/assets"
if [ -f "yii" ]; then
    chmod 755 yii 2>/dev/null || echo_color $WARNING "Aviso: Não foi possível ajustar permissões para o arquivo yii"
fi

echo_color $SUCCESS "Ambiente preparado com sucesso!"

# Executar o comando original
exec "$@" 