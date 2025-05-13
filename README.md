# Sistema de Gerenciamento de Despesas Pessoais API

API RESTful para gerenciamento de despesas pessoais, desenvolvida com Yii2 Framework.

![Licença](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)
![Yii2 Version](https://img.shields.io/badge/Yii2-2.0.47-green)
![Tests](https://img.shields.io/badge/Tests-Passing-brightgreen)

## 📋 Sobre o Projeto

Esta API permite o registro, visualização, edição e exclusão de despesas pessoais com suporte a:
- Autenticação JWT
- Categorização por tipo de despesa
- Filtragem por período e categoria
- Relatórios e resumos

## 🚀 Tecnologias Utilizadas

- **PHP 8.3+**
- **Yii2 Framework**
- **MySQL 5.7+**
- **JWT para autenticação**
- **Docker para desenvolvimento**
- **Codeception para testes**

## 💻 Instalação Rápida (Docker)

```bash
# Clone o repositório
git clone https://github.com/mkdev-2/api-despesas.git
cd api-despesas

# Configure o ambiente
cp .env.example .env
# Edite o arquivo .env conforme necessário

# Inicie os containers
docker-compose up -d
```

## 🏗 Arquitetura

O projeto segue uma arquitetura modular com:

- **Módulo financeiro**: Gerenciamento de despesas e relatórios
- **Módulo usuários**: Autenticação e gerenciamento de usuários

## 📚 Documentação

- [Documentação da API](API.md)
- [Documentação de testes](tests/README.md)

