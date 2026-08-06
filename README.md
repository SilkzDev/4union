# Sistema Financeiro · 4 Union

Sistema web de gestão financeira desenvolvido em PHP com MySQL, para controle de
receitas, despesas e categorias com autenticação de usuários.

Projeto acadêmico — SENAI, turma **TEC-INF-00041**, professor Luciano Amorim.

| Integrante | Cargo |
| --- | --- |
| Letícia | Líder Técnico |
| Jhon | Programador |
| Filipe | Designer |
| Jacob | Banco de Dados e Analista |

**Última entrega:** Sprint 07 — Módulo de Despesas (28/07/2026)

---

## Sumário

- [Funcionalidades](#funcionalidades)
- [Tecnologias](#tecnologias)
- [Estrutura do projeto](#estrutura-do-projeto)
- [Instalação](#instalação)
- [Banco de dados](#banco-de-dados)
- [Configuração de e-mail](#configuração-de-e-mail)
- [Padrões de código](#padrões-de-código)
- [Histórico de sprints](#histórico-de-sprints)
- [Limitações conhecidas](#limitações-conhecidas)
- [Próximos passos](#próximos-passos)

---

## Funcionalidades

### Autenticação

- **Login** com verificação de senha via `password_verify()` (hashes gerados por `password_hash()`)
- **Cadastro** de novos usuários com validação de e-mail duplicado
- **Recuperação de senha** por e-mail (Gmail SMTP via PHPMailer)
- **Redefinição de senha** com token *stateless* assinado por HMAC-SHA256, válido por 1 hora
- Bloqueio de acesso para usuários inativos
- `session_regenerate_id(true)` no login, contra fixação de sessão

### Dashboard

Painel com indicadores de **Saldo Atual**, **Receitas** e **Despesas**, calculados
a partir dos registros ativos (`ativo = 'S'`) do usuário logado. Sidebar recolhível
com estado persistido em `localStorage`.

### Módulo de Categorias

CRUD completo de categorias, tipadas como **Receita** ou **Despesa**. A exclusão
valida se há receitas ou despesas vinculadas antes de remover.

### Módulo de Receitas

CRUD completo com vínculo a Cliente, Categoria, Forma de Pagamento e Conta
Financeira. Status **Recebido** / **Pendente**.

### Módulo de Despesas *(Sprint 07)*

CRUD completo com:

| Recurso | Descrição |
| --- | --- |
| Campos relacionais | Fornecedor, Categoria, Forma de Pagamento e Conta Financeira, carregados por consulta ao banco |
| Dados do lançamento | Descrição, Valor, Data de Pagamento, Status (Pago/Pendente), Observações |
| Exclusão lógica | Registros são marcados como `ativo = 'N'`, nunca removidos fisicamente |
| Filtro por texto | Busca em descrição |
| Filtro por período | Intervalo entre data inicial e final |
| Filtros combinados | Categoria, fornecedor e status |
| Máscara monetária | Formatação `R$ 0.000,00` durante a digitação |

O ComboBox de Categoria lista **apenas categorias do tipo "Despesa"** — mesma regra
aplicada em Receitas para o tipo "Receita". Categorias cadastradas com o tipo errado
não aparecem no formulário.

---

## Tecnologias

- **PHP 7.4+** — sem framework, organizado por responsabilidade de arquivo
- **MySQL / MariaDB** — acesso exclusivamente via **PDO com prepared statements**
- **HTML5, CSS3 e JavaScript** puro (sem build step)
- **PHPMailer** — envio de e-mail via SMTP, incluído manualmente em `libs/` (sem Composer)
- **Font Awesome 6** — ícones, via CDN
- **XAMPP** — ambiente de desenvolvimento (Apache + MySQL)

---

## Estrutura do projeto

```
financeiro/
├── index.php               # Login (página inicial)
├── cadastro.php            # Cadastro de novo usuário
├── recuperar.php           # Solicitação de recuperação de senha
├── redefinir.php           # Redefinição via token
├── logout.php              # Encerramento de sessão
├── dashboard.php           # Painel com indicadores
├── popular.php             # Seed de usuários de teste (uso restrito, ver aviso)
│
├── config/
│   ├── conexao.php         # Conexão PDO
│   └── mailer.example.php  # Modelo de configuração de e-mail
│
├── pages/
│   ├── categorias/         # index, listar, cadastrar, editar, visualizar, salvar, excluir
│   ├── receitas/           # index, listar, cadastrar, editar, visualizar, salvar, excluir
│   └── despesas/           # index, listar, editar, visualizar, salvar, excluir
│
├── assets/
│   ├── css/                # Estilos por tela
│   └── js/                 # Scripts por módulo
│
├── libs/phpmailer/         # PHPMailer (ver Limitações conhecidas)
├── database/
│   ├── 01_schema_e_dados_iniciais.sql       # Dump completo do banco (obrigatório)
│   ├── 02_seed_receitas_e_despesas.sql      # Seed opcional de demonstração
│   ├── 03_seed_metas_e_clientes.sql         # Seed opcional (Dashboard Executivo)
│   ├── obsoleto_migration_despesas.sql      # Já incorporada no 01, não rodar
│   ├── referencia_consulta_fluxo_caixa.sql  # Documentação, não é script
│   └── referencia_consultas_dashboard_bi.sql # Documentação, não é script
└── img/
```

Cada módulo segue a mesma divisão de arquivos:

| Arquivo | Responsabilidade |
| --- | --- |
| `index.php` | Entrada do módulo |
| `listar.php` | Listagem, filtros e formulário de cadastro |
| `cadastrar.php` | Formulário de inclusão |
| `editar.php` | Formulário de alteração |
| `visualizar.php` | Detalhes do registro |
| `salvar.php` | Persistência (INSERT / UPDATE) |
| `excluir.php` | Remoção |

---

## Instalação

### Pré-requisitos

- XAMPP (ou Apache + PHP 7.4+ + MySQL/MariaDB)
- Extensões PHP: `pdo_mysql`, `openssl` (para envio de e-mail)

### Passo a passo

**1. Clone o repositório dentro de `htdocs`**

```bash
cd C:/xampp/htdocs
git clone https://github.com/SilkzDev/4union.git financeiro
```

**2. Crie o banco e importe o dump**

No phpMyAdmin, crie o banco `financeiro` e importe:

```
database/01_schema_e_dados_iniciais.sql
```

O dump já inclui a estrutura completa e a coluna `ativo` do módulo de despesas.

> Se você estiver atualizando uma instalação anterior à Sprint 07, essa
> migração já está incorporada no dump acima — não é preciso rodar
> `database/obsoleto_migration_despesas.sql` separadamente.

Opcionalmente, para não ver as telas zeradas, rode também os seeds de
demonstração (em qualquer ordem, nenhum depende do outro):

```
database/02_seed_receitas_e_despesas.sql
database/03_seed_metas_e_clientes.sql
```

**3. Configure a conexão**

Ajuste as credenciais em [config/conexao.php](config/conexao.php) se o seu MySQL
não usa o padrão do XAMPP (`root` sem senha).

**4. Configure o envio de e-mail**

```bash
cp config/mailer.example.php config/mailer.php
```

Preencha as constantes conforme a seção [Configuração de e-mail](#configuração-de-e-mail).
O arquivo `config/mailer.php` está no `.gitignore` e não deve ser versionado.

**5. Acesse**

```
http://localhost/financeiro/
```

Crie sua conta pela tela de cadastro e comece cadastrando **categorias** — sem elas,
os formulários de receitas e despesas ficam sem opções selecionáveis.

---

## Banco de dados

### Tabelas principais

| Tabela | Descrição |
| --- | --- |
| `usuarios` | Contas de acesso, com `perfil_id` e senha em hash |
| `perfis` | Perfis de acesso (admin, operador, editor) |
| `categorias` | Categorias por usuário, tipadas em Receita/Despesa |
| `receitas` | Lançamentos de entrada |
| `despesas` | Lançamentos de saída |
| `clientes` | Cadastro de clientes (origem das receitas) |
| `fornecedores` | Cadastro de fornecedores (destino das despesas) |
| `formas_pagamento` | Formas de pagamento disponíveis |
| `contas` | Contas financeiras (Caixa, Conta Corrente, Poupança) |

### Tabelas com estrutura criada, ainda sem uso na aplicação

`movimentacoes`, `metas` e `configuracoes` — previstas para as próximas sprints
(Fluxo de Caixa e Dashboards).

### Convenção de exclusão lógica

A maior parte das tabelas usa `ativo CHAR(1)` com `'S'` / `'N'`. Consultas de
listagem filtram por `ativo = 'S'`.

> **Exceção:** `usuarios.ativo` é gravado como `1` / `0` pelo código de cadastro e
> validado como inteiro no login, apesar de a coluna ser `CHAR(1)`. Se você inserir
> um usuário manualmente com `ativo = 'S'`, o login será recusado.

---

## Configuração de e-mail

Copie `config/mailer.example.php` para `config/mailer.php` e preencha:

```php
define('SMTP_EMAIL', 'seu-email@gmail.com');
define('SMTP_APP_PASSWORD', 'senha-de-app-de-16-digitos');
define('APP_SECRET', 'string-longa-e-aleatoria-exclusiva-desta-instalacao');
```

- **`SMTP_APP_PASSWORD`** exige uma *senha de app* do Google (verificação em duas
  etapas ativada na conta). A senha normal do Gmail não funciona.
- **`APP_SECRET`** assina os tokens de redefinição de senha. Trocar esse valor
  invalida todos os links de redefinição já enviados.

O token de redefinição é *stateless*: carrega o ID do usuário e a validade em
base64, assinado com HMAC-SHA256. Nada é gravado no banco, e qualquer adulteração
do payload quebra a assinatura.

---

## Padrões de código

- **Todas** as consultas usam PDO com prepared statements
- Toda página protegida verifica `$_SESSION['logado']` antes de qualquer saída
- Validação em duas camadas: JavaScript (máscara, limites de data) e PHP
  (campos obrigatórios, valor maior que zero)
- Saída HTML escapada com `htmlspecialchars()`
- Consultas filtradas por `usuario_id`, isolando os dados de cada conta
- CSS e JS isolados por tela, sem framework ou etapa de build

---

## Histórico de sprints

### Sprint 07 — Módulo de Despesas ✓

Objetivo: implementar o módulo de despesas reaproveitando a arquitetura das Sprints
05 e 06. **Concluído**, testado de ponta a ponta e integrado ao sistema.

**Requisitos entregues:** RF01 a RF13 — cadastrar, editar, excluir (lógico), listar
e pesquisar despesas, com os campos Fornecedor, Categoria, Forma de Pagamento,
Conta Financeira, Data de Pagamento, Valor, Status e Observações.

**Melhorias "Level Up":** filtro por período e máscara monetária.

**Alterações no banco:** coluna `despesas.ativo` para exclusão lógica; carga inicial
das tabelas `contas` e `fornecedores`, que existiam vazias.

**Testes realizados:**

| Cenário | Resultado |
| --- | --- |
| Cadastrar despesa com campos válidos | ✓ Registro salvo |
| Cadastrar despesa com valor = 0 | ✓ Mensagem de erro |
| Editar despesa (Pendente → Pago) | ✓ Alteração salva |
| Excluir despesa | ✓ Marcada como inativa, mantida no banco |
| Filtrar por status | ✓ Lista filtrada |
| Filtrar por período | ✓ Lista filtrada |
| Reflexo no dashboard | ✓ Soma apenas despesas ativas |

**Correção de sprint anterior:** o indicador de despesas do dashboard consultava
uma tabela `transacoes` inexistente e retornava sempre zero. A query passou a
consultar a tabela `despesas` real, com filtro `ativo = 'S'`.

**Problemas enfrentados durante a sprint:**

- Acentuação corrompida ao importar a migração via `mysql.exe` na linha de comando.
  Resolvido reexecutando as atualizações via PHP/PDO, com o mesmo charset `utf8`
  usado pela aplicação.
- Relato de que categorias não apareciam no ComboBox de despesas. Não era bug: o
  formulário filtra apenas categorias do tipo "Despesa", e as categorias testadas
  estavam cadastradas como "Receita".

### Sprints anteriores

- **Sprint 05** — Módulos de Receitas e Categorias, base arquitetural reaproveitada
- **Sprint 06** — Conceitos de acesso a dados

---

## Limitações conhecidas

- **PHPMailer incompleto no repositório.** Os arquivos em `libs/phpmailer/src/`
  estão truncados ou vazios. Para que a recuperação de senha funcione, baixe o
  [PHPMailer oficial](https://github.com/PHPMailer/PHPMailer) e substitua o
  conteúdo de `libs/phpmailer/src/`. Atenção também ao *case* do caminho: os
  `require_once` de `config/mailer.php` usam `libs/PHPMailer/src/`, que funciona no
  Windows mas quebra em servidor Linux.
- **`popular.php` é destrutivo.** Ele executa `TRUNCATE TABLE usuarios`, apagando
  todos os usuários cadastrados, e recria três contas de teste com senhas fixas
  no código. Use apenas em ambiente local, nunca em produção — idealmente, remova
  o arquivo antes de publicar.
- **Indicadores do dashboard são acumulados, não mensais.** Apesar do rótulo, as
  queries somam todos os lançamentos ativos do usuário, sem recorte por mês.
- **Exclusão física em Receitas e Categorias.** Os dois módulos ainda usam `DELETE`,
  enquanto Despesas já adota exclusão lógica.
- **Sem paginação nas listagens.** O volume de registros ainda é pequeno, mas as
  telas carregam todos os resultados de uma vez.

---

## Próximos passos

- **Fluxo de Caixa** (próxima sprint), consumindo os dados já cadastrados de
  receitas e despesas
- Padronizar a exclusão lógica também em Receitas e Categorias
- Recorte por período nos indicadores do dashboard
- Ativar as tabelas `movimentacoes`, `metas` e `configuracoes`

---

<sub>Sistema Financeiro 4 Union · Projeto acadêmico SENAI · Turma TEC-INF-00041</sub>
