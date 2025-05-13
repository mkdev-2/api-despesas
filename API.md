# API de Gerenciamento de Despesas Pessoais

## Visão Geral

Esta API RESTful fornece endpoints para gerenciamento de despesas pessoais, permitindo que os usuários registrem, consultem, atualizem e excluam suas despesas, bem como obtenham relatórios e resumos.

## Base URL

```
http://localhost:8080
```

## Autenticação

A API utiliza autenticação via JWT (JSON Web Token). Para acessar endpoints protegidos, é necessário incluir o token no cabeçalho de autorização:

```
Authorization: Bearer {seu_token}
```

### Obtenção do Token

Tokens podem ser obtidos através do endpoint de login ou registro.

## Endpoints

### Autenticação de Usuários

#### Registro de Usuário

```
POST /usuarios/auth/register
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

**Respostas de Erro:**
- `409 Conflict`: Email ou username já em uso
  ```json
  {
      "error": "Email já está em uso"
  }
  ```
  ou
  ```json
  {
      "error": "Nome de usuário já está em uso"
  }
  ```
- `422 Unprocessable Entity`: Dados inválidos (ex: username muito longo, email inválido)
  ```json
  {
      "error": "Não foi possível criar o usuário",
      "errors": {
          "username": ["O nome de usuário deve ter no máximo 20 caracteres"],
          "email": ["Email inválido"]
      }
  }
  ```

#### Login de Usuário

```
POST /usuarios/auth/login
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

**Respostas de Erro:**
- `400 Bad Request`: Campos obrigatórios ausentes
  ```json
  {
      "error": "Email e senha são obrigatórios"
  }
  ```
- `401 Unauthorized`: Credenciais inválidas
  ```json
  {
      "error": "Credenciais inválidas"
  }
  ```
- `405 Method Not Allowed`: Método não permitido (ex: tentativa de GET em endpoint POST)

#### Perfil do Usuário

```
GET /usuarios/auth/profile
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

#### Atualizar Perfil do Usuário

```
PUT /usuarios/auth/update-profile
```

Atualiza os dados do perfil do usuário autenticado.

**Parâmetros de Requisição:**
```json
{
    "username": "usuarioatualizado",
    "email": "novo@exemplo.com"
}
```

**Resposta de Sucesso (200 OK):**
```json
{
    "id": 5,
    "username": "usuarioatualizado",
    "email": "novo@exemplo.com",
    "updated_at": "2023-05-15 10:30:00"
}
```

### Gestão de Despesas

#### Listar Despesas

```
GET /financeiro/despesas
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
POST /financeiro/despesas/create
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
GET /financeiro/despesas/{id}
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
PUT /financeiro/despesas/{id}/update
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
DELETE /financeiro/despesas/{id}/delete
```

Exclui uma despesa (soft delete).

**Resposta de Sucesso (204 No Content)**

### Categorias e Resumos

#### Listar Categorias

```
GET /financeiro/despesas/categorias
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
GET /financeiro/despesas/resumo
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

## Códigos de Status

- `200 OK`: Requisição bem-sucedida
- `201 Created`: Recurso criado com sucesso
- `204 No Content`: Requisição bem-sucedida sem conteúdo de resposta
- `400 Bad Request`: Requisição inválida (por exemplo, campos obrigatórios ausentes)
- `401 Unauthorized`: Falha de autenticação ou credenciais inválidas
- `403 Forbidden`: Acesso proibido
- `404 Not Found`: Recurso não encontrado
- `405 Method Not Allowed`: Método HTTP não permitido para o endpoint (por exemplo, GET em um endpoint exclusivo para POST)
- `409 Conflict`: Conflito (por exemplo, email ou username já em uso durante o registro)
- `422 Unprocessable Entity`: Erro de validação nos dados submetidos
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

### Execução de Testes em Ambiente Docker

Para executar os testes em um ambiente Docker, siga estes passos:

1. **Preparação do Banco de Dados de Teste**:
```bash
# Dentro do contêiner da aplicação, execute:
docker exec -it despesas_app php ./scripts/prepare-test-db.php
```

2. **Execução dos Testes Unitários**:
```bash
# Executa todos os testes unitários
docker exec -it despesas_app php ./vendor/bin/codecept run unit

# Executa um teste unitário específico
docker exec -it despesas_app php ./vendor/bin/codecept run unit path/to/test
```

3. **Execução dos Testes de Integração**:
```bash
# Certifique-se que a configuração de conexão com o banco esteja correta em tests/integration.suite.yml
# O host deve ser 'despesas_db' para funcionar corretamente dentro do contêiner Docker
docker exec -it despesas_app php ./vendor/bin/codecept run integration
```

4. **Geração de Relatório de Cobertura**:
```bash
# Gerar relatório de cobertura de código
docker exec -it despesas_app php ./vendor/bin/codecept run --coverage --coverage-html
```

O relatório gerado estará disponível no diretório `tests/_output/coverage/`.

### Observações sobre os Testes

- A execução de testes em ambiente Docker requer configurações específicas para as conexões entre os serviços
- Os testes funcionais que exigem acesso HTTP entre contêineres podem exigir configurações adicionais de rede
- Os dados de teste são recriados a cada execução para garantir consistência nos resultados

### Modelos Testados

Os testes da API cobrem os seguintes modelos e funcionalidades:

- **User**: Autenticação, registro, validação de dados
- **LoginForm**: Login com email/username, validação de credenciais
- **Despesa**: CRUD completo, validações, categorização, filtros

## Limitações e Considerações

- A API implementa soft delete para despesas, mantendo o histórico no banco de dados
- As requisições são limitadas a 1000 por IP por hora
- O tamanho máximo de payload é de 5MB
- Tokens JWT expiram após 24 horas 