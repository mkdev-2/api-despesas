# Sistema de Gerenciamento de Despesas Pessoais API

API RESTful para gerenciamento de despesas pessoais, desenvolvida com Yii2 Framework.

![Licença](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue)
![Yii2 Version](https://img.shields.io/badge/Yii2-2.0.47-green)

## 📋 Sobre o Projeto

Esta API permite o registro, visualização, edição e exclusão de despesas pessoais com suporte a:
- Autenticação JWT
- Categorização por tipo de despesa
- Filtragem por período e categoria
- Relatórios e resumos
- Versionamento da API

## 🚀 Tecnologias Utilizadas

- **PHP 8.0+**
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
- **Módulo API**: Interface RESTful versionada (v1)

## 📚 Documentação

- [Documentação completa](README.md)
- [Documentação da API](API.md)
- [Documentação de testes](tests/README.md)

## 🧪 Testes

```bash
# Prepare o ambiente de teste
php scripts/prepare-test-db.php

# Execute todos os testes
./vendor/bin/codecept run
```

## 🤝 Contribuindo

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -m 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## 📝 Licença

Distribuído sob a licença MIT. Veja `LICENSE` para mais informações. 