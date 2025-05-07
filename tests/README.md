# Documentação de Testes

## Visão Geral

Este diretório contém os testes automatizados do sistema de gerenciamento de despesas pessoais. Utilizamos o framework Codeception para implementação dos testes, oferecendo uma abordagem estruturada para testes unitários, funcionais e de integração.

Os testes foram desenvolvidos para garantir a qualidade do código, verificar o comportamento esperado dos componentes individuais e validar a integração entre diferentes partes do sistema.

## Estrutura de Testes

A organização dos testes segue a estrutura padrão do Codeception:

```
tests/
├── _data/                      # Dados utilizados nos testes
├── _output/                    # Diretório para resultados e relatórios
├── _support/                   # Classes auxiliares para testes
├── functional/                 # Testes funcionais (simulação de requisições)
├── unit/                       # Testes unitários
│   ├── models/                 # Testes de modelos
│   │   ├── DespesaTest.php     # Testes do modelo Despesa
│   │   ├── LoginFormTest.php   # Testes do modelo LoginForm
│   │   └── UserTest.php        # Testes do modelo User
│   └── MockUserComponent.php   # Componente de mock para usuários em testes
├── acceptance/                 # Testes de aceitação
├── _bootstrap.php              # Arquivo de inicialização para todos os testes
├── functional.suite.yml        # Configuração da suíte de testes funcionais
├── unit.suite.yml              # Configuração da suíte de testes unitários
├── prepare-test-db.sh          # Script shell para preparação do banco de dados de teste
└── acceptance.suite.yml        # Configuração da suíte de testes de aceitação
```

## Configuração do Ambiente de Teste

Para configurar o ambiente de testes, siga os passos abaixo:

1. Prepare o banco de dados de teste (se necessário) usando um dos seguintes scripts:
   ```bash
   # Opção 1: Script shell nesta pasta
   ./tests/prepare-test-db.sh
   
   # Opção 2: Script PHP na pasta scripts/
   php scripts/prepare-test-db.php
   ```
   Ambos os scripts criam um banco de dados específico para testes, separado do banco de produção.

2. Para popular o banco de dados de teste com dados de exemplo:
   ```bash
   php scripts/seed-test-db.php
   ```

3. Verifique a configuração em `config/test.php`:
   - Desabilita sessões durante os testes
   - Configura componentes de aplicação específicos para o ambiente de teste
   - Define conexões com bancos de dados de teste

4. Instale as dependências necessárias (se ainda não estiverem instaladas):
   ```bash
   composer install
   ```

## Tipos de Testes

### Testes Unitários

Os testes unitários são focados em verificar o comportamento isolado de componentes individuais do sistema. Os principais testes incluem:

#### UserTest
- `testGetUser`: Testa a obtenção de um usuário pelo ID
- `testRegister`: Verifica o processo de registro de usuários
- `testSetPassword`: Avalia a funcionalidade de definição e validação de senhas
- `testGenerateAuthKey`: Testa a geração de chaves de autenticação

#### LoginFormTest
- `testLoginCorrectEmail`: Verifica o login com credenciais corretas usando email
- `testLoginWrongPassword`: Testa a rejeição de senhas incorretas
- `testLoginEmptyCredentials`: Verifica a validação de campos vazios
- `testLoginNoUser`: Testa o comportamento quando um usuário não existe

#### DespesaTest
- `testGetCategorias`: Verifica a obtenção da lista de categorias
- `testValidation`: Testa as regras de validação do modelo Despesa
- `testBeforeValidate`: Verifica o comportamento dos callbacks de pré-validação
- `testModelRelations`: Testa as relações entre Despesa e outros modelos

### Testes sem Banco de Dados

Para alguns testes, optamos por não depender diretamente do banco de dados para evitar problemas de integridade. Nesses casos, utilizamos:

- Mock objects para simular componentes da aplicação
- Configuração específica em `MockUserComponent.php` para simular a autenticação de usuários

## Como Executar os Testes

### Executar Todos os Testes
```bash
./vendor/bin/codecept run
```

### Executar Apenas Testes Unitários
```bash
./vendor/bin/codecept run unit
```

### Executar um Arquivo de Teste Específico
```bash
./vendor/bin/codecept run unit models/UserTest.php
```

### Executar um Teste Específico
```bash
./vendor/bin/codecept run unit models/UserTest:testRegister
```

### Executar Testes com Detalhes (Modo Debug)
```bash
./vendor/bin/codecept run unit --debug
```

### Executar Testes com Relatório de Cobertura
```bash
./vendor/bin/codecept run --coverage --coverage-html
```
O relatório HTML será gerado em `tests/_output/coverage/`.

## Mocks e Fixtures

### MockUserComponent

O arquivo `MockUserComponent.php` simula o componente de usuário para testes, implementando funcionalidades como:

- Login/logout simulado sem necessidade de sessão real
- Armazenamento de identidade do usuário
- Simulação de verificação de autenticação

Exemplo de uso:
```php
// No arquivo de teste
$this->mockUser->login($user);
$this->tester->assertNotNull($this->mockUser->identity);
```

### Fixtures de Dados

Para testes que requerem dados predefinidos, utilizamos:

- Criação manual de instâncias durante os testes
- Método `_before()` para preparação do ambiente de teste
- Método `_after()` para limpeza após cada teste

## Considerações Importantes e Desafios

Durante a implementação dos testes, identificamos e solucionamos os seguintes desafios:

### Limitações de Usernames

- **Problema**: A validação do modelo User restringe usernames a um máximo de 20 caracteres.
- **Solução**: Implementamos geração de usernames curtos usando o padrão:
  ```php
  $timestamp = time() % 10000; // Usar apenas os últimos 4 dígitos
  $username = 'tst_' . $timestamp;
  ```

### Conflitos de Emails e Usernames

- **Problema**: Testes executados em sequência ou paralelamente poderiam gerar conflitos de emails/usernames.
- **Solução**: Implementamos um sistema robusto para garantir unicidade:
  ```php
  private static $usedEmails = [];
  
  // No método _before de cada teste:
  $testMethod = isset($trace[1]['function']) ? $trace[1]['function'] : '';
  $testMethod = substr($testMethod, 0, 4); // Apenas os primeiros 4 caracteres
  $email = 'test_' . $testMethod . '_' . $random . '_' . substr($timestamp, -4) . '@example.com';
  
  // Verificar se o email já foi usado
  while (in_array($email, self::$usedEmails) && $attempts < 5) {
      // Gerar outro email...
  }
  
  // Registrar o email usado
  self::$usedEmails[] = $email;
  ```

### Códigos de Status HTTP

- **Problema**: Incompatibilidade entre códigos de status esperados e retornados pela API:
  - Emails/usernames duplicados deveriam retornar 409 (Conflict) em vez de 422 (Unprocessable Entity)
  - Login com credenciais inválidas deveria retornar 401 (Unauthorized) em vez de 422
  - GET em endpoints POST deveria retornar 405 (Method Not Allowed)
- **Solução**: Atualizamos os testes para usar os códigos corretos de acordo com a implementação real da API.

### Formato de Resposta JSON

- **Problema**: Alterações no formato de resposta JSON da API (especialmente em endpoints como `/api/despesas/categorias` e `/api/despesas/resumo`).
- **Solução**: Atualizamos os testes para verificar campos realmente existentes usando `seeResponseJsonMatchesJsonPath` em vez de comparações exatas de estrutura.

### Sessões em Testes

- **Problema**: Problemas com sessões durante testes, que causavam erros sobre headers já enviados.
- **Solução**: Implementamos uma classe `MockSession` que simula a sessão sem depender de cookies ou headers HTTP.

## Cobertura de Testes

Os testes atuais cobrem:
- Modelos principais da aplicação (User, Despesa)
- Formulários de login e autenticação
- Validações e regras de negócio

Áreas para expansão futura:
- Testes de API completos para todos os endpoints
- Testes de integração para fluxos complexos
- Testes de aceitação para interfaces de usuário 