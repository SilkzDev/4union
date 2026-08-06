<?php
// pages/fluxo_caixa/listar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
}

require_once "../../config/conexao.php";

$usuario_id   = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;
$usuario_nome = $_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? 'Usuário';

function formatarValorSemArredondar($valor) {
    if ($valor === null || $valor === '') return '0,00';
    $valorStr = (string)$valor;
    $partes = explode('.', $valorStr);

    $inteiro = number_format((float)$partes[0], 0, '', '.');
    $decimais = isset($partes[1]) ? $partes[1] : '00';

    if (strlen($decimais) < 2) {
        $decimais = str_pad($decimais, 2, '0', STR_PAD_RIGHT);
    } else {
        $decimais = substr($decimais, 0, 2);
    }

    return $inteiro . ',' . $decimais;
}

// Filtros GET (RF04, RF05, RF06 - Período, Conta, Categoria) + Level Up: Filtro por Status
$filtro_data_inicio = isset($_GET['f_data_inicio']) ? trim($_GET['f_data_inicio']) : '';
$filtro_data_fim    = isset($_GET['f_data_fim']) ? trim($_GET['f_data_fim']) : '';
$filtro_conta       = isset($_GET['f_conta']) ? trim($_GET['f_conta']) : '';
$filtro_categoria   = isset($_GET['f_categoria']) ? trim($_GET['f_categoria']) : '';
$filtro_status      = isset($_GET['f_status']) ? trim($_GET['f_status']) : '';

$tem_filtro_ativo = (!empty($filtro_data_inicio) || !empty($filtro_data_fim) || !empty($filtro_conta)
    || !empty($filtro_categoria) || !empty($filtro_status));

// 1. Carregar Contas Financeiras (para filtro)
try {
    $sql_ct = "SELECT id, nome FROM contas WHERE ativo = 'S' ORDER BY nome ASC";
    $stmt_ct = $conexao->query($sql_ct);
    $contas = $stmt_ct->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contas = [];
}

// 2. Carregar Categorias (Receita + Despesa) do usuário (para filtro)
try {
    $sql_cat = "SELECT id, nome, tipo
                FROM categorias
                WHERE usuario_id = :usuario_id
                  AND (ativo = 'S' OR ativo = '1' OR ativo IS NULL)
                ORDER BY tipo ASC, nome ASC";
    $stmt_cat = $conexao->prepare($sql_cat);
    $stmt_cat->execute(['usuario_id' => $usuario_id]);
    $categorias = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias = [];
}

// 3. Consulta Consolidada (UNION) - Receitas + Despesas => Fluxo de Caixa
try {
    $sql = "SELECT * FROM (
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
                WHERE d.usuario_id = :usuario_id2 AND d.ativo = 'S'
            ) AS fluxo
            WHERE 1=1";

    $params = ['usuario_id' => $usuario_id, 'usuario_id2' => $usuario_id];

    if (!empty($filtro_data_inicio)) {
        $sql .= " AND data >= :data_inicio";
        $params['data_inicio'] = $filtro_data_inicio;
    }
    if (!empty($filtro_data_fim)) {
        $sql .= " AND data <= :data_fim";
        $params['data_fim'] = $filtro_data_fim;
    }
    if (!empty($filtro_conta)) {
        $sql .= " AND conta_id = :conta_id";
        $params['conta_id'] = $filtro_conta;
    }
    if (!empty($filtro_categoria)) {
        $sql .= " AND categoria_id = :categoria_id";
        $params['categoria_id'] = $filtro_categoria;
    }
    if ($filtro_status === 'concluido') {
        $sql .= " AND status IN ('Recebido', 'Pago')";
    } elseif ($filtro_status === 'pendente') {
        $sql .= " AND status = 'Pendente'";
    }

    $sql .= " ORDER BY data ASC, id ASC";

    $stmt = $conexao->prepare($sql);
    $stmt->execute($params);
    $movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $movimentacoes = [];
}

// 4. Cálculo do Saldo Acumulado (cronológico) + Totais (RF02, RF03, RF07, RF08)
$total_receitas = 0.0;
$total_despesas = 0.0;
$saldo_acumulado = 0.0;

foreach ($movimentacoes as &$mov) {
    $total_receitas   += (float)$mov['entrada'];
    $total_despesas   += (float)$mov['saida'];
    $saldo_acumulado  += (float)$mov['entrada'] - (float)$mov['saida'];
    $mov['saldo']      = $saldo_acumulado;
}
unset($mov);

$saldo_atual         = $total_receitas - $total_despesas;
$total_movimentacoes = count($movimentacoes);

// Exibição: mais recente primeiro (mesmo padrão de Receitas/Despesas), saldo já calculado cronologicamente
$movimentacoes_exibicao = array_reverse($movimentacoes);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fluxo de Caixa - 4 Union</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/listar.css?v=<?= time() ?>">

    <script>
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed-init');
        }
    </script>

    <style>
        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .kpi-card { background: #ffffff; padding: 22px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .kpi-card h3 { font-size: 13px; color: #64748b; text-transform: uppercase; font-weight: 700; }
        .kpi-card .valor { font-size: 24px; font-weight: 700; color: #1e3c72; margin-top: 8px; }
        .kpi-card .valor-receita { color: #16a34a; }
        .kpi-card .valor-despesa { color: #dc2626; }
    </style>
</head>
<body>

    <?php $sidebarActive = 'fluxo_caixa'; $sidebarRoot = '../../'; include __DIR__ . '/../../partials/sidebar.php'; ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Fluxo de Caixa</h1>
                <p>Bem-vindo(a), <strong><?= htmlspecialchars($usuario_nome) ?></strong></p>
            </div>
            <div class="topbar-actions">
                <button type="button" id="btnToggleFiltros" class="btn-search" onclick="toggleFiltros()">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </div>
        </header>

        <!-- Indicadores Consolidados (RF02, RF03, RF07, RF08) -->
        <section class="kpi-grid">
            <div class="kpi-card">
                <h3>Total de Receitas</h3>
                <div class="valor valor-receita">R$ <?= formatarValorSemArredondar($total_receitas) ?></div>
            </div>
            <div class="kpi-card">
                <h3>Total de Despesas</h3>
                <div class="valor valor-despesa">R$ <?= formatarValorSemArredondar($total_despesas) ?></div>
            </div>
            <div class="kpi-card">
                <h3>Saldo Atual</h3>
                <div class="valor <?= $saldo_atual >= 0 ? 'valor-receita' : 'valor-despesa' ?>">R$ <?= formatarValorSemArredondar($saldo_atual) ?></div>
            </div>
            <div class="kpi-card">
                <h3>Movimentações</h3>
                <div class="valor"><?= $total_movimentacoes ?></div>
            </div>
        </section>

        <!-- ÁREA DE FILTROS (RF04, RF05, RF06 + Level Up: Status) -->
        <div id="painelFiltros" class="filter-section" style="<?= $tem_filtro_ativo ? 'display:block;' : 'display:none;'; ?>">
            <h4 style="margin-bottom:12px;"><i class="fas fa-filter"></i> Filtrar Fluxo de Caixa</h4>
            <form action="listar.php" method="GET">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Período - Início</label>
                        <input type="date" name="f_data_inicio" max="9999-12-31" value="<?= htmlspecialchars($filtro_data_inicio) ?>">
                    </div>
                    <div class="filter-group">
                        <label>Período - Fim</label>
                        <input type="date" name="f_data_fim" max="9999-12-31" value="<?= htmlspecialchars($filtro_data_fim) ?>">
                    </div>
                    <div class="filter-group">
                        <label>Conta</label>
                        <select name="f_conta">
                            <option value="">Todas</option>
                            <?php foreach ($contas as $ct): ?>
                                <option value="<?= $ct['id'] ?>" <?= $filtro_conta == $ct['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ct['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Categoria</label>
                        <select name="f_categoria">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $filtro_categoria == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nome']) ?> (<?= htmlspecialchars($cat['tipo']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="f_status">
                            <option value="">Todos</option>
                            <option value="concluido" <?= $filtro_status === 'concluido' ? 'selected' : '' ?>>Concluído (Recebido/Pago)</option>
                            <option value="pendente" <?= $filtro_status === 'pendente' ? 'selected' : '' ?>>Pendente</option>
                        </select>
                    </div>
                </div>
                <div class="filter-buttons">
                    <a href="listar.php" class="btn-clear">Limpar</a>
                    <button type="submit" class="btn-apply"><i class="fas fa-search"></i> Filtrar</button>
                </div>
            </form>
        </div>

        <div class="card">
            <h3 style="margin-bottom:15px;">Movimentações (RF01, RF09, RF10)</h3>

            <?php if (empty($movimentacoes_exibicao)): ?>
                <div style="text-align: center; padding: 40px 20px; color: #64748b;">
                    <i class="fas fa-scale-balanced" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px; display: block;"></i>
                    <h4 style="font-size: 18px; color: #334155; margin-bottom: 8px;">Nenhuma movimentação encontrada</h4>
                    <p style="font-size: 14px;">Cadastre Receitas ou Despesas para alimentar o Fluxo de Caixa automaticamente.</p>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Entrada</th>
                            <th>Saída</th>
                            <th>Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimentacoes_exibicao as $mov): ?>
                            <tr>
                                <td data-label="Data"><?= !empty($mov['data']) ? date('d/m/Y', strtotime($mov['data'])) : '-' ?></td>
                                <td data-label="Tipo">
                                    <span class="badge <?= $mov['tipo'] === 'Receita' ? 'badge-receita' : 'badge-despesa' ?>">
                                        <?= htmlspecialchars($mov['tipo']) ?>
                                    </span>
                                </td>
                                <td data-label="Descrição"><strong><?= htmlspecialchars($mov['descricao']) ?></strong></td>
                                <td data-label="Categoria"><?= htmlspecialchars($mov['categoria_nome'] ?? 'Sem Categoria') ?></td>
                                <td data-label="Entrada" style="color:#16a34a; font-weight:600;">
                                    <?= $mov['entrada'] > 0 ? 'R$ ' . formatarValorSemArredondar($mov['entrada']) : '-' ?>
                                </td>
                                <td data-label="Saída" style="color:#dc2626; font-weight:600;">
                                    <?= $mov['saida'] > 0 ? 'R$ ' . formatarValorSemArredondar($mov['saida']) : '-' ?>
                                </td>
                                <td data-label="Saldo" style="font-weight:700;">
                                    R$ <?= formatarValorSemArredondar($mov['saldo']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.documentElement.classList.remove('sidebar-collapsed-init');
            document.body.classList.toggle('sidebar-collapsed');

            const estaRecolhida = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', estaRecolhida ? 'true' : 'false');
        }

        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }

        function toggleFiltros() {
            var painel = document.getElementById('painelFiltros');
            if (painel) {
                var estaOculto = window.getComputedStyle(painel).display === 'none';
                painel.style.display = estaOculto ? 'block' : 'none';
            }
        }
    </script>
</body>
</html>
