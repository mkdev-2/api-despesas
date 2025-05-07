FROM php:8.3-fpm

# Argumentos definidos no docker-compose.yml
ARG user=www-data
ARG uid=1000

# Instalar dependências essenciais
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    default-mysql-client \
    iputils-ping \
    net-tools \
    --no-install-recommends && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Instalar extensões PHP
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl

# Instalar e habilitar Xdebug para cobertura de código somente se não for produção
ARG ENVIRONMENT=development
RUN if [ "$ENVIRONMENT" = "development" ]; then \
        pecl install xdebug && \
        docker-php-ext-enable xdebug && \
        echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini; \
    fi

# Obter o Composer mais recente
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Criar diretório do sistema
RUN mkdir -p /var/www/html

# Configurar usuário padrão
RUN usermod -u $uid $user

# Definir diretório de trabalho
WORKDIR /var/www/html

# Copiar configurações personalizadas do PHP
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Copiar a configuração do PHP-FPM para escutar em todas as interfaces
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copiar o script de inicialização primeiro
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Criar diretórios essenciais e configurar permissões
RUN mkdir -p /var/www/html/runtime /var/www/html/web/assets /var/www/html/config && \
    chown -R $user:$user /var/www/html

# Instalar ferramentas de espera
ENV WAIT_VERSION 2.12.1
ADD --chmod=755 https://github.com/ufoscout/docker-compose-wait/releases/download/$WAIT_VERSION/wait /wait

# Definir variáveis de ambiente para conexão com o banco de dados
ENV DB_HOST=despesas_db
ENV DB_PORT=3306
ENV DB_DATABASE=gerenciamento_despesas
ENV DB_DATABASE_TEST=gerenciamento_despesas_test
ENV DB_USERNAME=despesas
ENV DB_PASSWORD=root
ENV DB_CHARSET=utf8mb4
ENV DOCKER_ENV=1

# Criar configurações do banco de dados
COPY docker/config/db.php /var/www/html/config/db.php
COPY docker/config/test_db.php /var/www/html/config/test_db.php

# Ajustar permissões dos arquivos de configuração
RUN chmod 644 /var/www/html/config/db.php /var/www/html/config/test_db.php

# Copiar o código da aplicação
COPY --chown=$user:$user . /var/www/html

# Garantir que os arquivos db.php não sejam substituídos
RUN if [ -f /var/www/html/config/db.php.example ] && [ ! -f /var/www/html/config/db.php ]; then \
        cp /var/www/html/config/db.php.example /var/www/html/config/db.php; \
    fi && \
    chown $user:$user /var/www/html/config/db.php && \
    chmod 644 /var/www/html/config/db.php && \
    if [ -f /var/www/html/config/test_db.php.example ] && [ ! -f /var/www/html/config/test_db.php ]; then \
        cp /var/www/html/config/test_db.php.example /var/www/html/config/test_db.php; \
    fi && \
    chown $user:$user /var/www/html/config/test_db.php && \
    chmod 644 /var/www/html/config/test_db.php

# Garantir que a configuração de PHP-FPM não seja sobrescrita
RUN cp /usr/local/etc/php-fpm.d/www.conf /usr/local/etc/php-fpm.d/www.conf.original && \
    chmod 644 /usr/local/etc/php-fpm.d/www.conf

# Garantir permissões corretas para diretórios importantes
RUN chmod -R 777 /var/www/html/runtime /var/www/html/web/assets && \
    find /var/www/html -type d -exec chmod 755 {} \; && \
    find /var/www/html -type f -exec chmod 644 {} \; && \
    if [ -f /var/www/html/yii ]; then chmod 755 /var/www/html/yii; fi

# Expor a porta padrão do php-fpm
EXPOSE 9000

# Configurar a entrada para o script de inicialização
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]

