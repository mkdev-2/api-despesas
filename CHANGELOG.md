# Registro de Alterações

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [Não Lançado]

### Adicionado
- Estrutura inicial do projeto com Yii2 Framework
- Módulos: financeiro, usuários e API (v1)
- Autenticação JWT para API
- CRUD completo de despesas com categorização
- Relatórios e resumos financeiros
- Filtros por categoria e período para despesas
- Suporte a Docker para ambiente de desenvolvimento
- Testes automatizados com Codeception

### Modificado
- Reorganização da estrutura de diretórios para arquitetura modular
- Migração para PHP 8.1
- Atualização da documentação para refletir o comportamento real da API e as melhorias nos testes

### Corrigido
- Problemas com CORS em requisições de API
- Validação de dados de entrada
- Testes funcionais para conformidade com a API real:
  - Geração de usernames únicos respeitando o limite de 20 caracteres
  - Códigos de status HTTP adequados para diferentes cenários de erro
  - Verificação do formato de resposta JSON nos endpoints `/api/despesas/categorias` e `/api/despesas/resumo`
  - Problemas de conflito com emails/usernames duplicados durante a execução sequencial de testes
  - Problemas de sessão e headers HTTP durante testes funcionais

## [0.1.0] - 2025-05-07

- Versão inicial do projeto 