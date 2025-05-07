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
- Versionamento da API

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
git clone https://github.com/seu-usuario/gerenciamento-despesas.git
cd gerenciamento-despesas/backend

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
- [Frontend para Teste Web](em breve)

```bash
# Prepare o ambiente de teste
php scripts/prepare-test-db.php

# Execute todos os testes
./vendor/bin/codecept run
```

### Melhorias Recentes nos Testes

Implementamos diversas melhorias para tornar os testes mais robustos e confiáveis:

- **Geração inteligente de dados de teste**: Sistema para criar usernames e emails únicos para cada teste, evitando conflitos
- **Alinhamento com comportamento real da API**: Códigos de status HTTP corretos para cada cenário (409 para conflitos, 401 para autenticação falha)
- **Verificação flexível de resposta JSON**: Adaptação às mudanças de formato nos endpoints como `/api/despesas/categorias` e `/api/despesas/resumo`
- **Solução para problemas de sessão**: Mock de sessões para evitar erros de headers HTTP durante testes

Todos os testes estão passando com sucesso, garantindo a qualidade e confiabilidade da API.

## 🤝 Contribuindo

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -m 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## 📝 Licença

Distribuído sob a licença MIT. 