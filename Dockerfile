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

# Copiar o script de inicialização primeiro
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Criar diretórios essenciais e configurar permissões
RUN mkdir -p /var/www/html/runtime /var/www/html/web/assets

# Instalar ferramentas de espera
ENV WAIT_VERSION 2.12.1
ADD --chmod=755 https://github.com/ufoscout/docker-compose-wait/releases/download/$WAIT_VERSION/wait /wait

# Definir imagem não-root para reduzir riscos de segurança
USER $user

# Copiar o código da aplicação (após configuração do usuário)
COPY --chown=$user:$user . /var/www/html

# Expor a porta padrão do php-fpm
EXPOSE 9000

# Configurar a entrada para o script de inicialização
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]

