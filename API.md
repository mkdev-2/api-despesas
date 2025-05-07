# API de Gerenciamento de Despesas Pessoais

## Visão Geral

Esta API RESTful fornece endpoints para gerenciamento de despesas pessoais, permitindo que os usuários registrem, consultem, atualizem e excluam suas despesas, bem como obtenham relatórios e resumos.

## Base URL

```
http://localhost:8080
```

## Versões da API

A API agora suporta versionamento através de prefixos de URL. Atualmente, existem duas maneiras de acessar a API:

### API v1 (Recomendada)
```
http://localhost:8080/api/v1/[recurso]
```

### API Legada (Compatibilidade)
```
http://localhost:8080/api/[recurso]
```

> **Nota**: Recomendamos o uso da versão mais recente da API (/api/v1/). As rotas legadas (/api/) são mantidas temporariamente para compatibilidade, mas poderão ser descontinuadas no futuro.

## Autenticação

A API utiliza autenticação via JWT (JSON Web Token). Para acessar endpoints protegidos, é necessário incluir o token no cabeçalho de autorização:

```
Authorization: Bearer {seu_token}
```

### Obtenção do Token

Tokens podem ser obtidos através do endpoint de login ou registro.

## Endpoints

> Exemplos abaixo utilizam o novo prefixo `/api/v1/`. Se você estiver utilizando a versão legada, remova o `/v1` do caminho.

### Autenticação de Usuários

#### Registro de Usuário

```
POST /api/v1/auth/register
```

Cria uma nova conta de usuário.

**Parâmetros de Requisição:**
```json
{
    "username": "novousuario",
    "email": "usuario@exemplo.com",
    "password": "senha123"
}
```

**Resposta de Sucesso (201 Created):**
```json
{
    "message": "Usuário criado com sucesso",
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5...",
    "user": {
        "id": 5,
        "username": "novousuario",
        "email": "usuario@exemplo.com"
    }
}
```

#### Login de Usuário

```
POST /api/v1/auth/login
```

Autentica um usuário existente.

**Parâmetros de Requisição:**
```json
{
    "email": "usuario@exemplo.com",
    "password": "senha123"
}
```

**Resposta de Sucesso (200 OK):**
```json
{
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5...",
    "user": {
        "id": 5,
        "username": "novousuario",
        "email": "usuario@exemplo.com"
    }
}
```

#### Perfil do Usuário

```
GET /api/v1/auth/profile
```

Retorna os dados do usuário autenticado.

**Resposta de Sucesso (200 OK):**
```json
{
    "id": 5,
    "username": "novousuario",
    "email": "usuario@exemplo.com",
    "created_at": "2023-05-10 14:30:00"
}
```

### Gestão de Despesas

#### Listar Despesas

```
GET /api/v1/despesas
```

Retorna a lista de despesas do usuário autenticado.

**Parâmetros de Consulta:**
- `page`: Número da página (padrão: 1)
- `per_page`: Itens por página (padrão: 10)
- `categoria`: Filtrar por categoria
- `mes`: Filtrar por mês (1-12)
- `ano`: Filtrar por ano
- `data_inicio`: Filtrar a partir desta data (formato: YYYY-MM-DD)
- `data_fim`: Filtrar até esta data (formato: YYYY-MM-DD)
- `descricao`: Filtrar por descrição (busca parcial)
- `ordem_asc`: Ordenação ascendente por data (true/false, padrão: false)

**Resposta de Sucesso (200 OK):**
```json
{
    "items": [
        {
            "id": 123,
            "descricao": "Supermercado",
            "categoria": "alimentacao",
            "valor": 150.75,
            "data": "2023-05-10",
            "created_at": "2023-05-10 14:35:00",
            "updated_at": "2023-05-10 14:35:00"
        },
        // ... mais itens
    ],
    "_meta": {
        "totalCount": 45,
        "pageCount": 5,
        "currentPage": 1,
        "perPage": 10
    }
}
```

#### Criar Despesa

```
POST /api/v1/despesas/create
```

Cria uma nova despesa para o usuário autenticado.

**Parâmetros de Requisição:**
```json
{
    "descricao": "Restaurante",
    "categoria": "alimentacao",
    "valor": 85.90,
    "data": "2023-05-11"
}
```

**Resposta de Sucesso (201 Created):**
```json
{
    "id": 124,
    "descricao": "Restaurante",
    "categoria": "alimentacao",
    "valor": 85.90,
    "data": "2023-05-11",
    "user_id": 5,
    "created_at": "2023-05-11 10:20:00",
    "updated_at": "2023-05-11 10:20:00"
}
```

#### Detalhar Despesa

```
GET /api/v1/despesas/{id}
```

Retorna os detalhes de uma despesa específica.

**Resposta de Sucesso (200 OK):**
```json
{
    "id": 124,
    "descricao": "Restaurante",
    "categoria": "alimentacao",
    "valor": 85.90,
    "data": "2023-05-11",
    "user_id": 5,
    "created_at": "2023-05-11 10:20:00",
    "updated_at": "2023-05-11 10:20:00"
}
```

#### Atualizar Despesa

```
PUT /api/v1/despesas/{id}/update
```

Atualiza os dados de uma despesa existente.

**Parâmetros de Requisição:**
```json
{
    "descricao": "Restaurante Italiano",
    "categoria": "alimentacao",
    "valor": 95.90,
    "data": "2023-05-11"
}
```

**Resposta de Sucesso (200 OK):**
```json
{
    "id": 124,
    "descricao": "Restaurante Italiano",
    "categoria": "alimentacao",
    "valor": 95.90,
    "data": "2023-05-11",
    "user_id": 5,
    "created_at": "2023-05-11 10:20:00",
    "updated_at": "2023-05-11 11:15:00"
}
```

#### Excluir Despesa

```
DELETE /api/v1/despesas/{id}/delete
```

Exclui uma despesa (soft delete).

**Resposta de Sucesso (204 No Content)**

### Categorias e Resumos

#### Listar Categorias

```
GET /api/v1/despesas/categorias
```

Retorna a lista de categorias disponíveis com informações visuais.

**Resposta de Sucesso (200 OK):**
```json
[
    {
        "id": "alimentacao",
        "nome": "Alimentação",
        "icone": "fas fa-utensils",
        "cor": "bg-blue-500",
        "corBg": "bg-blue-100 dark:bg-blue-900",
        "corTexto": "text-blue-800 dark:text-blue-200"
    },
    // ... mais categorias
]
```

#### Resumo de Despesas

```
GET /api/v1/despesas/resumo
```

Retorna um resumo das despesas agrupadas por categoria.

**Parâmetros de Consulta:**
- `mes`: Filtrar por mês (1-12)
- `ano`: Filtrar por ano
- `data_inicio`: Filtrar a partir desta data (formato: YYYY-MM-DD)
- `data_fim`: Filtrar até esta data (formato: YYYY-MM-DD)
- `categoria`: Filtrar por categoria específica

**Resposta de Sucesso (200 OK):**
```json
{
    "periodo": {
        "inicio": "2023-05-01",
        "fim": "2023-05-31"
    },
    "categorias": [
        {
            "categoria": "alimentacao",
            "categoria_nome": "Alimentação",
            "total": 450.75,
            "quantidade": 5,
            "icone": "fas fa-utensils",
            "cor": "bg-blue-500",
            "corBg": "bg-blue-100 dark:bg-blue-900",
            "corTexto": "text-blue-800 dark:text-blue-200",
            "percentual": 35.20
        },
        // ... mais categorias
    ],
    "total": 1280.45,
    "evolucao_mensal": [
        {
            "ano": 2023,
            "mes": 1,
            "total": 1350.30
        },
        // ... mais meses
    ]
}
```

#### Relatórios Detalhados

```
GET /api/v1/despesas/relatorios
```

Retorna relatórios detalhados com insights e tendências.

**Parâmetros de Consulta:**
- `data_inicio`: Data inicial (formato: YYYY-MM-DD)
- `data_fim`: Data final (formato: YYYY-MM-DD)
- `categoria`: Filtrar por categoria específica

**Resposta de Sucesso (200 OK):**
```json
{
    "filtros": {
        "dataInicio": "2023-03-01",
        "dataFim": "2023-05-31",
        "categoria": null
    },
    "resumo": {
        "totalDespesas": 3650.80,
        "qtdDespesas": 42,
        "mediaMensal": 1216.93,
        "maiorDespesa": {
            "valor": 520.00,
            "descricao": "Aluguel",
            "data": "2023-05-05",
            "categoria": {
                "id": "moradia",
                "nome": "Moradia",
                "icone": "fas fa-home",
                "cor": "bg-red-500"
            }
        },
        "categoriaMaisUsada": {
            "id": "alimentacao",
            "nome": "Alimentação",
            "icone": "fas fa-utensils",
            "cor": "bg-blue-500",
            "total": 1250.35
        }
    },
    "graficos": {
        "evolucaoTempo": [
            {
                "periodo": "2023-03",
                "total": 1185.45
            },
            // ... mais períodos
        ],
        "despesasPorCategoria": [
            {
                "categoria": "alimentacao",
                "nome": "Alimentação",
                "total": 1250.35,
                "icone": "fas fa-utensils",
                "cor": "bg-blue-500",
                "percentual": 34.25
            },
            // ... mais categorias
        ]
    },
    "tendencias": {
        "variacaoPercentual": 5.2,
        "comparacaoPeriodoAnterior": {
            "periodo": {
                "inicio": "2022-12-01",
                "fim": "2023-02-28"
            },
            "total": 3470.25
        }
    }
}
```

## Códigos de Status

- `200 OK`: Requisição bem-sucedida
- `201 Created`: Recurso criado com sucesso
- `204 No Content`: Requisição bem-sucedida sem conteúdo de resposta
- `400 Bad Request`: Requisição inválida
- `401 Unauthorized`: Falha de autenticação
- `403 Forbidden`: Acesso proibido
- `404 Not Found`: Recurso não encontrado
- `409 Conflict`: Conflito (ex: email já em uso)
- `422 Unprocessable Entity`: Erro de validação
- `500 Internal Server Error`: Erro interno do servidor

## Formatos de Data

Todos os campos de data utilizam o formato ISO 8601:
- Datas: `YYYY-MM-DD`
- Data e hora: `YYYY-MM-DD HH:MM:SS`

## Testes da API

### Testes Automatizados

A API possui uma suíte completa de testes automatizados para garantir seu funcionamento correto:

1. **Testes unitários**: Verificam o funcionamento isolado dos modelos, validações e regras de negócio.
2. **Testes de integração**: Verificam a interação entre diferentes componentes do sistema.

### Configuração do Ambiente de Teste

Para executar os testes da API:

1. Prepare o banco de dados de teste:
```bash
./scripts/prepare-test-db.php
```

2. Execute os testes:
```bash
./vendor/bin/codecept run
```

### Testes Manuais

Para testar manualmente a API, você pode usar ferramentas como:

- **curl**: Diretamente do terminal
- **Postman**: Interface gráfica para testar APIs
- **Insomnia**: Alternativa ao Postman

Exemplo de teste manual com curl:

```bash
# Login
curl -X POST http://localhost:8080/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"demo@example.com","password":"demo123"}'

# Listar despesas (com o token obtido no login)
curl -X GET http://localhost:8080/despesas \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

### Modelos Testados

Os testes da API cobrem os seguintes modelos e funcionalidades:

- **User**: Autenticação, registro, validação de dados
- **LoginForm**: Login com email/username, validação de credenciais
- **Despesa**: CRUD completo, validações, categorização, filtros

### Cobertura de Testes

A cobertura de testes da API é monitorada regularmente para garantir a qualidade do código. Para gerar um relatório de cobertura:

```bash
./vendor/bin/codecept run --coverage --coverage-html
```

O relatório gerado estará disponível em `tests/_output/coverage/`.

## Limitações e Considerações

- A API implementa soft delete para despesas, mantendo o histórico no banco de dados
- As requisições são limitadas a 1000 por IP por hora
- O tamanho máximo de payload é de 5MB
- Tokens JWT expiram após 24 horas 