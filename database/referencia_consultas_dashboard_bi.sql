-- Sprint 09: Business Intelligence (Finance Dashboard Pro)
-- Consultas usadas em dashboard.php para os 8 KPIs obrigatorios e os 4 graficos.
-- Nenhuma alteracao de schema foi necessaria: tudo e calculado em cima das
-- tabelas ja existentes (receitas, despesas, categorias, formas_pagamento).

-- === KPIs de Volume/Performance/Operacional ===

-- Receita Total, Numero de Receitas, Maior Receita
SELECT COALESCE(SUM(valor), 0) AS total, COUNT(*) AS qtd, COALESCE(MAX(valor), 0) AS maior
FROM receitas
WHERE usuario_id = :usuario_id AND ativo = 'S';

-- Despesa Total, Numero de Despesas
SELECT COALESCE(SUM(valor), 0) AS total, COUNT(*) AS qtd
FROM despesas
WHERE usuario_id = :usuario_id AND ativo = 'S';

-- Saldo Atual / Lucro Liquido = Receita Total - Despesa Total (calculado em PHP)
-- Ticket Medio = Receita Total / Numero de Receitas (calculado em PHP)

-- === Grafico 1 (Comparacao): Receita x Despesa ===
-- Usa os mesmos totais das consultas de KPI acima.

-- === Grafico 2 (Distribuicao): Receitas por Categoria ===
SELECT COALESCE(c.nome, 'Sem Categoria') AS categoria, SUM(r.valor) AS total
FROM receitas r
LEFT JOIN categorias c ON r.categoria_id = c.id
WHERE r.usuario_id = :usuario_id AND r.ativo = 'S'
GROUP BY categoria
ORDER BY total DESC;

-- === Grafico 3 (Comparacao): Despesas por Categoria - gargalos financeiros ===
SELECT COALESCE(c.nome, 'Sem Categoria') AS categoria, SUM(d.valor) AS total
FROM despesas d
LEFT JOIN categorias c ON d.categoria_id = c.id
WHERE d.usuario_id = :usuario_id AND d.ativo = 'S'
GROUP BY categoria
ORDER BY total DESC;

-- === Grafico 4 (Evolucao): Evolucao Mensal das Receitas (ultimos 6 meses) ===
SELECT DATE_FORMAT(data_recebimento, '%Y-%m') AS mes, SUM(valor) AS total
FROM receitas
WHERE usuario_id = :usuario_id AND ativo = 'S'
  AND data_recebimento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
GROUP BY mes
ORDER BY mes ASC;

-- === Grafico 5 (Operacional): Formas de Pagamento (Receitas + Despesas) ===
SELECT COALESCE(fp.descricao, 'Nao informado') AS forma, COUNT(*) AS qtd
FROM (
    SELECT forma_pagamento_id FROM receitas WHERE usuario_id = :usuario_id AND ativo = 'S'
    UNION ALL
    SELECT forma_pagamento_id FROM despesas WHERE usuario_id = :usuario_id AND ativo = 'S'
) t
LEFT JOIN formas_pagamento fp ON t.forma_pagamento_id = fp.id
GROUP BY forma
ORDER BY qtd DESC;

-- === Level Up: Crescimento Percentual (%) - mes atual vs mes anterior ===
SELECT
    SUM(CASE WHEN DATE_FORMAT(data_recebimento, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN valor ELSE 0 END) AS mes_atual,
    SUM(CASE WHEN DATE_FORMAT(data_recebimento, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m') THEN valor ELSE 0 END) AS mes_anterior
FROM receitas
WHERE usuario_id = :usuario_id AND ativo = 'S';
-- Mesma logica aplicada a despesas, trocando data_recebimento por data_pagamento.
-- Percentual = ((mes_atual - mes_anterior) / mes_anterior) * 100, so calculado
-- quando ha valor no mes anterior (evita divisao por zero).

-- === Filtros dinamicos (RF04, RF05) ===
-- Todas as consultas acima recebem os mesmos fragmentos de filtro, montados uma
-- unica vez em PHP (funcao montarFiltroSql) e reaproveitados por KPIs e graficos,
-- garantindo que o dashboard inteiro reflita sempre a mesma janela de dados:
--
--   AND <coluna_data> >= :data_inicio     -- data_recebimento (receitas)
--   AND <coluna_data> <= :data_fim        -- data_pagamento   (despesas)
--   AND categoria_id  =  :categoria_id
--
-- Nas consultas com UNION, os placeholders recebem sufixo (_r / _d) para nao
-- repetir o mesmo nome nos dois lados da uniao.
