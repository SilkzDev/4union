# Relatório da Sprint 10 — Dashboard Executivo

**Projeto:** 4 Union Fintech  
**Sprint:** 10 — Inteligência Estratégica para Tomada de Decisão  
**Data:** 2026-08-04  
**Equipe:** 4 Union

---

## Objetivo

Desenvolver um **Dashboard Executivo** em uma única tela que forneça clareza absoluta para gestores financeiros, respondendo perguntas estratégicas em menos de 1 minuto.

A diferença em relação ao Dashboard BI (Sprint 09): deixar de ser um painel operacional ("O que aconteceu?") e tornar-se um painel estratégico ("Por que aconteceu e o que devemos fazer agora?"). O foco passou de dados históricos para **informação destilada para ação imediata**.

---

## KPIs Implementados

| # | KPI | Fórmula / Fonte SQL | Observação |
|---|-----|---------------------|------------|
| 1 | Receita Total | `SUM(valor)` FROM receitas | Filtros de período e categoria |
| 2 | Despesa Total | `SUM(valor)` FROM despesas | Filtros de período e categoria |
| 3 | Lucro Líquido | PHP: Receita − Despesa | Colorido: verde se positivo, vermelho se negativo |
| 4 | Margem de Lucro (%) | PHP: (Lucro ÷ Receita) × 100 | Destaque verde se ≥ 20% |
| 5 | Ticket Médio | PHP: Receita ÷ `COUNT(receitas)` | Ticket médio por transação |
| 6 | Maior Receita | `MAX(valor)` FROM receitas | Pico de receita no período |
| 7 | Maior Despesa | `MAX(valor)` FROM despesas | Pico de gasto no período |
| 8 | Qtd de Clientes | `COUNT(*)` FROM clientes WHERE ativo='S' | Total global |
| 9 | Qtd de Fornecedores | `COUNT(*)` FROM fornecedores WHERE ativo='S' | Total global |
| 10 | Saldo Atual | PHP: Receita − Despesa | Alias do Lucro Líquido; base para alertas |

Os KPIs 1 a 5 são exibidos como **cartões primários** (mais destacados). Os KPIs 6 a 10 aparecem em cartões secundários menores.

---

## Gráficos Desenvolvidos

| # | Título | Tipo | Fonte | Justificativa |
|---|--------|------|-------|---------------|
| 1 | Receita x Despesa por Mês | Linha dupla | Últimos 12 meses (GROUP BY mes) | Evolução temporal com dois datasets lado a lado — ideal para detectar tendências de convergência |
| 2 | Evolução do Lucro | Área (fill) | PHP: receita_mes − despesa_mes | Área preenchida evidencia períodos de prejuízo visualmente |
| 3 | Receita por Categoria | Rosca (Doughnut) | JOIN receitas + categorias | Proporção entre fontes de receita; rosca revela a parte mais importante de forma intuitiva |
| 4 | Receitas por Forma de Pagamento | Pizza (Pie) | JOIN receitas + formas_pagamento | Distribuição de meios; pizza com poucas fatias funciona melhor que barra |
| 5 | Despesas por Categoria (Top 10) | Barra horizontal | JOIN despesas + categorias LIMIT 10 | Barra horizontal facilita leitura de rótulos longos; Top 10 mantém o gráfico limpo |
| 6 | Ranking de Clientes | Barra horizontal | JOIN receitas + clientes LIMIT 10 | Classifica clientes por volume de receita gerada |
| 7 | Ranking de Categorias | Barra horizontal | JOIN receitas + categorias LIMIT 10 | Identifica quais categorias mais faturam no período |

---

## Comparativos Criados

### Comparativo Mensal (Receitas)
- **Mês atual** vs **mês anterior** (mesmo ano)
- Variação percentual: `(atual − anterior) / anterior × 100`
- Indicador visual: verde (↑) se positivo, vermelho (↓) se negativo

### Comparativo Anual (Receitas)
- **Ano atual** vs **ano anterior**
- Mesma lógica de variação percentual
- Permite identificar sazonalidade entre anos

---

## Metas Definidas

O sistema de metas usa a tabela `metas` (já existente no schema desde a Sprint anterior) com três registros padrão criados pelo script `database/03_seed_metas_e_clientes.sql`:

| Indicador | Meta | Tipo | Lógica de Semáforo |
|-----------|------|------|--------------------|
| Meta de Receita Mensal | R$ 5.000,00 | Receita | Verde ≥ 100% / Amarelo ≥ 70% / Vermelho < 70% |
| Limite de Despesas | R$ 3.000,00 | Despesa | Verde ≤ 100% (dentro do limite) / Amarelo ≤ 130% / Vermelho > 130% |
| Meta de Lucro Líquido | R$ 2.000,00 | Receita | Verde ≥ 100% / Amarelo ≥ 70% / Vermelho < 70% |

O `valor_realizado` e `percentual` são **calculados dinamicamente em PHP** no momento da requisição, garantindo que os semáforos sempre reflitam o estado atual dos dados — sem necessidade de job ou trigger.

---

## Análise SWOT

A análise SWOT é gerada **dinamicamente** com base nos KPIs calculados, garantindo que reflita o estado real dos dados em vez de um texto estático.

### Lógica dos Quadrantes

**Forças (Strengths) — verde**
- Margem de lucro ≥ 20% → sinaliza lucratividade saudável
- Crescimento de receitas no mês > 0% → negócio em expansão
- Ticket médio calculado → prova que há transações ativas
- Clientes cadastrados → base ativa

**Fraquezas (Weaknesses) — vermelho**
- Margem < 10% → operação pressionada
- Resultado negativo → prejuízo no período
- Sem clientes cadastrados → risco de concentração
- Sem receitas no período

**Oportunidades (Opportunities) — azul**
- Crescimento mensal > 10% → momento para escalar
- Crescimento anual positivo → tendência favorável
- Base de clientes ativa → possibilidade de fidelização
- Expansão para novas categorias (fixo, lembrete estratégico)

**Ameaças (Threats) — amarelo**
- Despesas crescendo mais rápido que receitas → erosão de margem
- Margem entre 0% e 15% → zona de atenção
- Despesas > Receitas → caixa negativo
- Sazonalidade e concentração de receita (fixo, alertas permanentes)

---

## Funcionalidades Técnicas Entregues

| Requisito | Status |
|-----------|--------|
| [RF01] KPIs Estratégicos (10 indicadores) | ✅ Entregue |
| [RF02] Comparativo de Períodos (mensal e anual) | ✅ Entregue |
| [RF03] Rankings Top 10 (clientes e categorias) | ✅ Entregue |
| [RF04] Filtros Avançados (data, categoria, quick-select) | ✅ Entregue |
| [RF05] Metas e Desempenho (tabela semáforos) | ✅ Entregue |
| [RF06] Análise SWOT dinâmica | ✅ Entregue |
| [RF07] Auto-refresh (countdown 5 min) | ✅ Entregue |
| [RF08] Exportar PDF | ⬜ Opcional (não implementado nesta sprint) |
| Tema dark executivo | ✅ Entregue |
| 7 Gráficos Chart.js animados | ✅ Entregue |
| Sidebar com link para executivo.php (todos os módulos) | ✅ Entregue |

---

## Dificuldades Encontradas

1. **Merge dos meses para gráfico dual-line:** Receitas e despesas podem ter meses sem dados em um dos lados do UNION. Resolvido com merge em PHP via array associativo por chave `YYYY-MM`, garantindo que ambas as séries tenham os mesmos rótulos no eixo X.

2. **Lógica invertida de semáforo para Despesas:** Uma meta de despesa (limite máximo) tem lógica oposta à de receita — estar abaixo do limite é "verde". Foi necessário diferenciar o cálculo por `tipo` da meta.

3. **SWOT sempre com conteúdo:** Quando não há dados suficientes (ex: banco zerado), os quadrantes ficam vazios. Resolvido com fallbacks — cada quadrante sempre exibe ao menos 1 item, seja calculado ou estratégico fixo.

4. **Tabela `clientes` vazia:** O ranking de clientes não exibia dados pois a tabela estava sem registros. Criado seed no `03_seed_metas_e_clientes.sql` com 5 clientes demo para demonstrar o gráfico.

5. **Legibilidade em tema dark:** Padrões do Chart.js (`Chart.defaults.color`, `borderColor`) precisaram ser sobrescritos globalmente para adaptar os textos e grades ao fundo escuro sem CSS adicional por gráfico.
