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
    <link rel="stylesheet" href="../../assets/css/listar.css">

    <script>
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed-init');
        }
    </script>

    <style>
        .sidebar .brand { display: flex !important; align-items: center !important; justify-content: space-between !important; gap: 10px; }
        .sidebar .brand-title { display: flex; align-items: center; gap: 8px; white-space: nowrap; }
        .btn-toggle-sidebar { background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.15); color: #cbd5e1; font-size: 0.85rem; width: 30px; height: 30px; border-radius: 6px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .btn-toggle-sidebar:hover { background: rgba(255, 255, 255, 0.25); color: #ffffff; }
        .btn-toggle-sidebar i { transition: transform 0.3s ease; }
        .sidebar, .main-content { transition: all 0.3s ease-in-out !important; }
        html.sidebar-collapsed-init body, body.sidebar-collapsed { --sidebar-w: 70px; }
        html.sidebar-collapsed-init .sidebar, body.sidebar-collapsed .sidebar { width: 70px !important; min-width: 70px !important; padding-left: 8px !important; padding-right: 8px !important; overflow: hidden; }
        html.sidebar-collapsed-init .sidebar .brand, body.sidebar-collapsed .sidebar .brand { justify-content: center !important; }
        html.sidebar-collapsed-init .sidebar .brand-title, body.sidebar-collapsed .sidebar .brand-title { display: none !important; }
        html.sidebar-collapsed-init .sidebar .btn-toggle-sidebar i, body.sidebar-collapsed .sidebar .btn-toggle-sidebar i { transform: rotate(180deg); }
        html.sidebar-collapsed-init .sidebar nav a span, body.sidebar-collapsed .sidebar nav a span { display: none !important; }
        html.sidebar-collapsed-init .sidebar nav a, body.sidebar-collapsed .sidebar nav a { justify-content: center !important; padding: 12px 0 !important; text-align: center; }
        html.sidebar-collapsed-init .sidebar nav a i, body.sidebar-collapsed .sidebar nav a i { margin-right: 0 !important; font-size: 1.25rem !important; }
        html.sidebar-collapsed-init .main-content, body.sidebar-collapsed .main-content { margin-left: 70px !important; width: calc(100% - 70px) !important; }

        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
        .kpi-card { background: #ffffff; padding: 22px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .kpi-card h3 { font-size: 13px; color: #64748b; text-transform: uppercase; font-weight: 700; }
        .kpi-card .valor { font-size: 24px; font-weight: 700; color: #1e3c72; margin-top: 8px; }
        .kpi-card .valor-receita { color: #16a34a; }
        .kpi-card .valor-despesa { color: #dc2626; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="brand">
            <span class="brand-title"><i class="fas fa-chart-line"></i> <span>4 Union</span></span>
            <button type="button" class="btn-toggle-sidebar" onclick="toggleSidebar()" title="Recolher/Expandir Menu">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <nav>
            <a href="../../dashboard.php"><i class="fas fa-home"></i> <span>Visão Geral</span></a>
            <a href="../receitas/listar.php"><i class="fas fa-hand-holding-dollar"></i> <span>Receitas</span></a>
            <a href="../despesas/listar.php"><i class="fas fa-file-invoice-dollar"></i> <span>Despesas</span></a>
            <a href="listar.php" class="active"><i class="fas fa-scale-balanced"></i> <span>Fluxo de Caixa</span></a>
            <a href="../categorias/listar.php"><i class="fas fa-tags"></i> <span>Gerenciar Categorias</span></a>
            <a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Sair</span></a>
        </nav>
    </aside>

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
                                <td><?= !empty($mov['data']) ? date('d/m/Y', strtotime($mov['data'])) : '-' ?></td>
                                <td>
                                    <span class="badge <?= $mov['tipo'] === 'Receita' ? 'badge-receita' : 'badge-despesa' ?>">
                                        <?= htmlspecialchars($mov['tipo']) ?>
                                    </span>
                                </td>
                                <td><strong><?= htmlspecialchars($mov['descricao']) ?></strong></td>
                                <td><?= htmlspecialchars($mov['categoria_nome'] ?? 'Sem Categoria') ?></td>
                                <td style="color:#16a34a; font-weight:600;">
                                    <?= $mov['entrada'] > 0 ? 'R$ ' . formatarValorSemArredondar($mov['entrada']) : '-' ?>
                                </td>
                                <td style="color:#dc2626; font-weight:600;">
                                    <?= $mov['saida'] > 0 ? 'R$ ' . formatarValorSemArredondar($mov['saida']) : '-' ?>
                                </td>
                                <td style="font-weight:700;">
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
