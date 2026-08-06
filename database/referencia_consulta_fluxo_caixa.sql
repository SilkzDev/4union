-- Sprint 08: Fluxo de Caixa Inteligente
-- Consulta de consolidação usada em pages/fluxo_caixa/listar.php
-- Unifica Receitas e Despesas (UNION ALL) em uma única linha do tempo,
-- sem exigir nenhuma digitação manual: os dados são 100% herdados
-- das tabelas `receitas` e `despesas` já existentes.

SELECT * FROM (
    SELECT r.id, 'Receita' AS tipo, r.data_recebimento AS data, r.descricao,
           r.categoria_id, c1.nome AS categoria_nome, r.conta_id,
           r.valor AS entrada, 0 AS saida, r.status
    FROM receitas r
    LEFT JOIN categorias c1 ON r.categoria_id = c1.id
    WHERE r.usuario_id = :usuario_id AND r.ativo = 'S'

    UNION ALL

    SELECT d.id, 'Despesa' AS tipo, d.data_pagamento AS data, d.descricao,
           d.categoria_id, c2.nome AS categoria_nome, d.conta_id,
           0 AS entrada, d.valor AS saida, d.status
    FROM despesas d
    LEFT JOIN categorias c2 ON d.categoria_id = c2.id
    WHERE d.usuario_id = :usuario_id AND d.ativo = 'S'
) AS fluxo
-- Filtros dinâmicos aplicados na query externa (RF04, RF05, RF06):
-- AND data >= :data_inicio
-- AND data <= :data_fim
-- AND conta_id = :conta_id
-- AND categoria_id = :categoria_id
-- AND status IN ('Recebido', 'Pago')   -- Level Up: filtro por status "Concluído"
-- AND status = 'Pendente'              -- Level Up: filtro por status "Pendente"
ORDER BY data ASC, id ASC;

-- O saldo acumulado (RF02, RF07) é calculado em PHP percorrendo o resultado
-- em ordem cronológica ascendente (mais antigo -> mais recente), somando
-- entrada e subtraindo saída a cada linha. Na tela, a listagem é exibida
-- em ordem cronológica decrescente (mais recente primeiro), mantendo o
-- saldo já calculado de cada movimentação.
