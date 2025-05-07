#!/bin/bash

# Script para preparar o banco de dados de teste
# Este script deve ser executado antes dos testes unitários

# Definindo variáveis
DB_HOST="localhost"
DB_PORT="3307"
DB_USER="user"
DB_PASS="password"
DB_NAME="gerenciamento_despesas_test"

# Recriando o banco de dados de teste
echo "Recriando o banco de dados de teste..."
mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS -e "DROP DATABASE IF EXISTS $DB_NAME; CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Executando migrations para criar a estrutura do banco de dados
echo "Aplicando migrations para criar a estrutura do banco de dados..."
cd "$(dirname "$0")/.." && php yii migrate/up --interactive=0 --migrationPath=@app/migrations --db=db_test

echo "Banco de dados de teste preparado com sucesso!" 