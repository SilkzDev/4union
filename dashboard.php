<?php
// dashboard.php - Painel Principal (Sprint 09: Business Intelligence)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Controle de Acesso: Redireciona para o login se o usuário não estiver autenticado
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit;
}

// Importa a conexão com o banco de dados
require_once "config/conexao.php";

// Captura informações do usuário logado na sessão (com fallbacks flexíveis)
$usuario_id     = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;
$usuario_nome   = $_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? 'Usuário';
$usuario_perfil = $_SESSION['usuario_perfil'] ?? $_SESSION['perfil'] ?? 'operador';

// Filtros GET (RF04, RF05 - Interação Dinâmica). Valem para TODOS os KPIs e gráficos.
$filtro_data_inicio = isset($_GET['f_data_inicio']) ? trim($_GET['f_data_inicio']) : '';
$filtro_data_fim    = isset($_GET['f_data_fim']) ? trim($_GET['f_data_fim']) : '';
$filtro_categoria   = isset($_GET['f_categoria']) ? trim($_GET['f_categoria']) : '';

$tem_filtro_periodo = ($filtro_data_inicio !== '' || $filtro_data_fim !== '');
$tem_filtro_ativo   = ($tem_filtro_periodo || $filtro_categoria !== '');

/**
 * Monta o trecho WHERE dos filtros. Como Receitas e Despesas usam nomes de
 * coluna de data diferentes, a coluna entra por parâmetro; o sufixo permite
 * reaproveitar o mesmo filtro nos dois lados de um UNION sem repetir o nome
 * do placeholder.
 */
function montarFiltroSql($coluna_data, $inicio, $fim, $categoria, $sufixo = '') {
    $sql = '';
    if ($inicio !== '')    $sql .= " AND {$coluna_data} >= :data_inicio{$sufixo}";
    if ($fim !== '')       $sql .= " AND {$coluna_data} <= :data_fim{$sufixo}";
    if ($categoria !== '') $sql .= " AND categoria_id = :categoria_id{$sufixo}";
    return $sql;
}

function montarFiltroParams($inicio, $fim, $categoria, $sufixo = '') {
    $params = [];
    if ($inicio !== '')    $params['data_inicio' . $sufixo] = $inicio;
    if ($fim !== '')       $params['data_fim' . $sufixo] = $fim;
    if ($categoria !== '') $params['categoria_id' . $sufixo] = $categoria;
    return $params;
}

// KPIs (RF01, RF02, RF06 - Visão Integrada), somas/contagens calculadas no banco (SUM/COUNT)
$receita_total   = 0.0;
$despesa_total   = 0.0;
$numero_receitas = 0;
$numero_despesas = 0;
$maior_receita   = 0.0;

// Level Up: Crescimento Percentual (%) - mês atual vs mês anterior
$crescimento_receita = null;
$crescimento_despesa = null;

// Dados para os gráficos (Chart.js)
$grafico_receita_despesa   = ['receita' => 0.0, 'despesa' => 0.0];
$grafico_cat_receitas      = [];
$grafico_cat_despesas      = [];
$grafico_evolucao          = [];
$grafico_formas_pagamento  = [];
$ultimas_movimentacoes     = [];
$categorias                = [];

if ($usuario_id) {
    try {
        // Lista de categorias do usuário, para alimentar o ComboBox do filtro
        $stmt = $conexao->prepare("
            SELECT id, nome, tipo
            FROM categorias
            WHERE usuario_id = :usuario_id
              AND (ativo = 'S' OR ativo = '1' OR ativo IS NULL)
            ORDER BY tipo ASC, nome ASC
        ");
        $stmt->execute(['usuario_id' => $usuario_id]);
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // KPIs de Receita: total, quantidade e maior valor
        $stmt = $conexao->prepare("
            SELECT COALESCE(SUM(valor), 0) AS total, COUNT(*) AS qtd, COALESCE(MAX(valor), 0) AS maior
            FROM receitas
            WHERE usuario_id = :usuario_id AND ativo = 'S'
            " . montarFiltroSql('data_recebimento', $filtro_data_inicio, $filtro_data_fim, $filtro_categoria) . "
        ");
        $stmt->execute(array_merge(
            ['usuario_id' => $usuario_id],
            montarFiltroParams($filtro_data_inicio, $filtro_data_fim, $filtro_categoria)
        ));
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        $receita_total   = (float)$r['total'];
        $numero_receitas = (int)$r['qtd'];
        $maior_receita   = (float)$r['maior'];

        // KPIs de Despesa: total e quantidade
        $stmt = $conexao->prepare("
            SELECT COALESCE(SUM(valor), 0) AS total, COUNT(*) AS qtd
            FROM despesas
            WHERE usuario_id = :usuario_id AND ativo = 'S'
            " . montarFiltroSql('data_pagamento', $filtro_data_inicio, $filtro_data_fim, $filtro_categoria) . "
        ");
        $stmt->execute(array_merge(
            ['usuario_id' => $usuario_id],
            montarFiltroParams($filtro_data_inicio, $filtro_data_fim, $filtro_categoria)
        ));
        $d = $stmt->fetch(PDO::FETCH_ASSOC);
        $despesa_total   = (float)$d['total'];
        $numero_despesas = (int)$d['qtd'];

        // Gráfico 1 (Comparação): Receita x Despesa
        $grafico_receita_despesa = ['receita' => $receita_total, 'despesa' => $despesa_total];

        // Gráfico 2 (Distribuição): Receitas por Categoria - maiores fontes de faturamento
        $stmt = $conexao->prepare("
            SELECT COALESCE(c.nome, 'Sem Categoria') AS categoria, SUM(r.valor) AS total
            FROM receitas r
            LEFT JOIN categorias c ON r.categoria_id = c.id
            WHERE r.usuario_id = :usuario_id AND r.ativo = 'S'
            " . montarFiltroSql('r.data_recebimento', $filtro_data_inicio, $filtro_data_fim, '') . "
            " . ($filtro_categoria !== '' ? " AND r.categoria_id = :categoria_id" : "") . "
            GROUP BY categoria
            ORDER BY total DESC
        ");
        $stmt->execute(array_merge(
            ['usuario_id' => $usuario_id],
            montarFiltroParams($filtro_data_inicio, $filtro_data_fim, $filtro_categoria)
        ));
        $grafico_cat_receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gráfico 3 (Comparação): Despesas por Categoria - identificar gargalos financeiros
        $stmt = $conexao->prepare("
            SELECT COALESCE(c.nome, 'Sem Categoria') AS categoria, SUM(d.valor) AS total
            FROM despesas d
            LEFT JOIN categorias c ON d.categoria_id = c.id
            WHERE d.usuario_id = :usuario_id AND d.ativo = 'S'
            " . montarFiltroSql('d.data_pagamento', $filtro_data_inicio, $filtro_data_fim, '') . "
            " . ($filtro_categoria !== '' ? " AND d.categoria_id = :categoria_id" : "") . "
            GROUP BY categoria
            ORDER BY total DESC
        ");
        $stmt->execute(array_merge(
            ['usuario_id' => $usuario_id],
            montarFiltroParams($filtro_data_inicio, $filtro_data_fim, $filtro_categoria)
        ));
        $grafico_cat_despesas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gráfico 4 (Evolução): receitas ao longo do tempo. Sem filtro de período,
        // mostra os últimos 6 meses; com filtro, respeita a janela escolhida.
        $sql_evolucao = "
            SELECT DATE_FORMAT(data_recebimento, '%Y-%m') AS mes, SUM(valor) AS total
            FROM receitas
            WHERE usuario_id = :usuario_id AND ativo = 'S'
        ";
        if ($tem_filtro_periodo) {
            $sql_evolucao .= montarFiltroSql('data_recebimento', $filtro_data_inicio, $filtro_data_fim, $filtro_categoria);
        } else {
            $sql_evolucao .= " AND data_recebimento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
            if ($filtro_categoria !== '') {
                $sql_evolucao .= " AND categoria_id = :categoria_id";
            }
        }
        $sql_evolucao .= " GROUP BY mes ORDER BY mes ASC";

        $stmt = $conexao->prepare($sql_evolucao);
        $stmt->execute(array_merge(
            ['usuario_id' => $usuario_id],
            montarFiltroParams($tem_filtro_periodo ? $filtro_data_inicio : '', $tem_filtro_periodo ? $filtro_data_fim : '', $filtro_categoria)
        ));
        $grafico_evolucao = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gráfico 5 (Operacional): Formas de Pagamento (Receitas + Despesas)
        $stmt = $conexao->prepare("
            SELECT COALESCE(fp.descricao, 'Não informado') AS forma, COUNT(*) AS qtd
            FROM (
                SELECT forma_pagamento_id FROM receitas
                WHERE usuario_id = :usuario_id1 AND ativo = 'S'
                " . montarFiltroSql('data_recebimento', $filtro_data_inicio, $filtro_data_fim, $filtro_categoria, '_r') . "
                UNION ALL
                SELECT forma_pagamento_id FROM despesas
                WHERE usuario_id = :usuario_id2 AND ativo = 'S'
                " . montarFiltroSql('data_pagamento', $filtro_data_inicio, $filtro_data_fim, $filtro_categoria, '_d') . "
            ) t
            LEFT JOIN formas_pagamento fp ON t.forma_pagamento_id = fp.id
            GROUP BY forma
            ORDER BY qtd DESC
        ");
        $stmt->execute(array_merge(
            ['usuario_id1' => $usuario_id, 'usuario_id2' => $usuario_id],
            montarFiltroParams($filtro_data_inicio, $filtro_data_fim, $filtro_categoria, '_r'),
            montarFiltroParams($filtro_data_inicio, $filtro_data_fim, $filtro_categoria, '_d')
        ));
        $grafico_formas_pagamento = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Level Up: Crescimento Percentual (%). Compara mês atual x mês anterior,
        // então só faz sentido quando nenhuma janela de tempo foi escolhida.
        if (!$tem_filtro_periodo) {
            $filtro_cat_sql = $filtro_categoria !== '' ? " AND categoria_id = :categoria_id" : "";
            $filtro_cat_par = $filtro_categoria !== '' ? ['categoria_id' => $filtro_categoria] : [];

            $stmt = $conexao->prepare("
                SELECT
                    SUM(CASE WHEN DATE_FORMAT(data_recebimento, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN valor ELSE 0 END) AS mes_atual,
                    SUM(CASE WHEN DATE_FORMAT(data_recebimento, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m') THEN valor ELSE 0 END) AS mes_anterior
                FROM receitas
                WHERE usuario_id = :usuario_id AND ativo = 'S' {$filtro_cat_sql}
            ");
            $stmt->execute(array_merge(['usuario_id' => $usuario_id], $filtro_cat_par));
            $rc = $stmt->fetch(PDO::FETCH_ASSOC);
            $rec_mes_anterior = (float)($rc['mes_anterior'] ?? 0);
            if ($rec_mes_anterior > 0) {
                $crescimento_receita = (((float)$rc['mes_atual'] - $rec_mes_anterior) / $rec_mes_anterior) * 100;
            }

            $stmt = $conexao->prepare("
                SELECT
                    SUM(CASE WHEN DATE_FORMAT(data_pagamento, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m') THEN valor ELSE 0 END) AS mes_atual,
                    SUM(CASE WHEN DATE_FORMAT(data_pagamento, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m') THEN valor ELSE 0 END) AS mes_anterior
                FROM despesas
                WHERE usuario_id = :usuario_id AND ativo = 'S' {$filtro_cat_sql}
            ");
            $stmt->execute(array_merge(['usuario_id' => $usuario_id], $filtro_cat_par));
            $dc = $stmt->fetch(PDO::FETCH_ASSOC);
            $desp_mes_anterior = (float)($dc['mes_anterior'] ?? 0);
            if ($desp_mes_anterior > 0) {
                $crescimento_despesa = (((float)$dc['mes_atual'] - $desp_mes_anterior) / $desp_mes_anterior) * 100;
            }
        }

        // Últimas Movimentações (reaproveita a consolidação Receitas+Despesas da Sprint 08)
        $stmt = $conexao->prepare("
            SELECT * FROM (
                SELECT r.id, 'Receita' AS tipo, r.data_recebimento AS data, r.descricao, r.valor AS entrada, 0 AS saida
                FROM receitas r
                WHERE r.usuario_id = :usuario_id1 AND r.ativo = 'S'
                " . montarFiltroSql('r.data_recebimento', $filtro_data_inicio, $filtro_data_fim, '', '_r') . "
                " . ($filtro_categoria !== '' ? " AND r.categoria_id = :categoria_id_r" : "") . "

                UNION ALL

                SELECT d.id, 'Despesa' AS tipo, d.data_pagamento AS data, d.descricao, 0 AS entrada, d.valor AS saida
                FROM despesas d
                WHERE d.usuario_id = :usuario_id2 AND d.ativo = 'S'
                " . montarFiltroSql('d.data_pagamento', $filtro_data_inicio, $filtro_data_fim, '', '_d') . "
                " . ($filtro_categoria !== '' ? " AND d.categoria_id = :categoria_id_d" : "") . "
            ) AS fluxo
            ORDER BY data DESC, id DESC
            LIMIT 6
        ");
        $stmt->execute(array_merge(
            ['usuario_id1' => $usuario_id, 'usuario_id2' => $usuario_id],
            montarFiltroParams($filtro_data_inicio, $filtro_data_fim, $filtro_categoria, '_r'),
            montarFiltroParams($filtro_data_inicio, $filtro_data_fim, $filtro_categoria, '_d')
        ));
        $ultimas_movimentacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        // Mantém tudo zerado/vazio em caso de erro, sem quebrar a tela
    }
}

// KPIs de Performance (calculados em cima dos totais já apurados)
$saldo_atual   = $receita_total - $despesa_total;
$lucro_liquido = $receita_total - $despesa_total;
$ticket_medio  = $numero_receitas > 0 ? $receita_total / $numero_receitas : 0.0;

// Labels amigáveis (mm/yyyy) para o eixo do gráfico de evolução.
// O dia é fixado em 01 porque createFromFormat('Y-m') completaria com o dia de
// hoje, estourando para o mês seguinte em meses curtos.
foreach ($grafico_evolucao as &$ponto) {
    $data_mes = DateTime::createFromFormat('Y-m-d', $ponto['mes'] . '-01');
    $ponto['mes_label'] = $data_mes ? $data_mes->format('m/Y') : $ponto['mes'];
}
unset($ponto);

// Paleta categórica fixa (ordem nunca é ciclada), usada nos gráficos por categoria
$paleta_categorica = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#898781'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - 4 Union</title>
    <!-- FontAwesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js (Sprint 09) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <!-- Arquivo CSS isolado -->
    <link rel="stylesheet" href="assets/css/dashboard.css?v=<?= time() ?>">

    <!-- Anti-flicker: Aplica o estado recolhido antes da renderização do DOM -->
    <script>
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed-init');
        }
    </script>

    <style>
        /* Estilos do Botão Toggle e Transições da Sidebar */
        .sidebar .brand {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 10px;
        }

        .sidebar .brand-title {
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-toggle-sidebar {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #cbd5e1;
            font-size: 0.85rem;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .btn-toggle-sidebar:hover {
            background: rgba(255, 255, 255, 0.25);
            color: #ffffff;
        }

        .btn-toggle-sidebar i {
            transition: transform 0.3s ease;
        }

        .sidebar, .main-content {
            transition: all 0.3s ease-in-out !important;
        }

        /* Regras quando a Sidebar está Recolhida */
        html.sidebar-collapsed-init body,
        body.sidebar-collapsed {
            --sidebar-w: 70px;
        }

        html.sidebar-collapsed-init .sidebar,
        body.sidebar-collapsed .sidebar {
            width: 70px !important;
            min-width: 70px !important;
            padding-left: 8px !important;
            padding-right: 8px !important;
            overflow: hidden;
        }

        html.sidebar-collapsed-init .sidebar .brand,
        body.sidebar-collapsed .sidebar .brand {
            justify-content: center !important;
        }

        html.sidebar-collapsed-init .sidebar .brand-title,
        body.sidebar-collapsed .sidebar .brand-title {
            display: none !important;
        }

        html.sidebar-collapsed-init .sidebar .btn-toggle-sidebar i,
        body.sidebar-collapsed .sidebar .btn-toggle-sidebar i {
            transform: rotate(180deg);
        }

        html.sidebar-collapsed-init .sidebar nav a span,
        body.sidebar-collapsed .sidebar nav a span {
            display: none !important;
        }

        html.sidebar-collapsed-init .sidebar nav a,
        body.sidebar-collapsed .sidebar nav a {
            justify-content: center !important;
            padding: 12px 0 !important;
            text-align: center;
        }

        html.sidebar-collapsed-init .sidebar nav a i,
        body.sidebar-collapsed .sidebar nav a i {
            margin-right: 0 !important;
            font-size: 1.25rem !important;
        }

        html.sidebar-collapsed-init .main-content,
        body.sidebar-collapsed .main-content {
            margin-left: 70px !important;
            width: calc(100% - 70px) !important;
        }
    </style>
</head>
<body>

    <!-- Sidebar de Navegação Esquerda -->
    <aside class="sidebar">
        <div class="brand">
            <span class="brand-title"><i class="fas fa-chart-line"></i> <span>4 Union</span></span>

            <button type="button" class="btn-toggle-sidebar" onclick="toggleSidebar()" title="Recolher/Expandir Menu">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <nav>
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i> <span>Visão Geral</span></a>
            <a href="pages/receitas/index.php"><i class="fas fa-hand-holding-dollar"></i> <span>Receitas</span></a>
            <a href="pages/despesas/listar.php"><i class="fas fa-file-invoice-dollar"></i> <span>Despesas</span></a>
            <a href="pages/fluxo_caixa/listar.php"><i class="fas fa-scale-balanced"></i> <span>Fluxo de Caixa</span></a>
            <a href="pages/categorias/listar.php"><i class="fas fa-tags"></i> <span>Gerenciar Categorias</span></a>
            <!-- Botão Sair movido para o Sidebar -->
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> <span>Sair</span></a>
        </nav>
    </aside>

    <!-- Conteúdo Principal -->
    <main class="main-content">

        <!-- Topbar Superior -->
        <header class="topbar">
            <div class="topbar-title">
                <h1>Painel Financeiro</h1>
                <p>Bem-vindo(a), <strong><?= htmlspecialchars($usuario_nome); ?></strong> (<?= htmlspecialchars($usuario_perfil); ?>)</p>
            </div>

            <!-- Topbar Actions Atualizado com Novo Atalho -->
            <div class="topbar-actions">
                <button type="button" class="btn-categoria-topo" onclick="toggleFiltros()">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <a href="pages/receitas/index.php" class="btn-categoria-topo" style="background-color: #2e7d32;">
                    <i class="fas fa-plus-circle"></i> Nova Receita
                </a>
                <a href="pages/categorias/listar.php?nova=1" class="btn-categoria-topo">
                    <i class="fas fa-tag"></i> Adicionar Categorias
                </a>
            </div>
        </header>

        <!-- ÁREA DE FILTROS (RF04, RF05) - afeta todos os KPIs e gráficos -->
        <div id="painelFiltros" class="filter-section" style="<?= $tem_filtro_ativo ? 'display:block;' : 'display:none;'; ?>">
            <h4 style="margin-bottom:12px; color:#334155;"><i class="fas fa-filter"></i> Filtrar Indicadores</h4>
            <form action="dashboard.php" method="GET">
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
                        <label>Categoria</label>
                        <select name="f_categoria">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $filtro_categoria == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nome']) ?> (<?= htmlspecialchars($cat['tipo']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="filter-buttons">
                    <a href="dashboard.php" class="btn-clear">Limpar</a>
                    <button type="submit" class="btn-apply"><i class="fas fa-search"></i> Aplicar</button>
                </div>
            </form>
        </div>

        <?php if ($tem_filtro_ativo): ?>
            <div class="filter-aviso">
                <i class="fas fa-circle-info"></i>
                Exibindo apenas os dados filtrados.
                <a href="dashboard.php">Limpar filtros</a>
            </div>
        <?php endif; ?>

        <!-- Prioridade 1: Cartões de Destaque (Leitura Imediata) -->
        <section class="cards-grid">
            <div class="card">
                <h3>RECEITA TOTAL</h3>
                <div class="value value-receita">R$ <?= number_format($receita_total, 2, ',', '.'); ?></div>
                <?php if ($crescimento_receita !== null): ?>
                    <div class="growth-badge <?= $crescimento_receita >= 0 ? 'growth-up' : 'growth-down' ?>">
                        <i class="fas fa-arrow-<?= $crescimento_receita >= 0 ? 'up' : 'down' ?>"></i>
                        <?= number_format(abs($crescimento_receita), 1, ',', '.') ?>% vs mês anterior
                    </div>
                <?php endif; ?>
            </div>
            <div class="card">
                <h3>DESPESA TOTAL</h3>
                <div class="value value-despesa">R$ <?= number_format($despesa_total, 2, ',', '.'); ?></div>
                <?php if ($crescimento_despesa !== null): ?>
                    <div class="growth-badge <?= $crescimento_despesa <= 0 ? 'growth-up' : 'growth-down' ?>">
                        <i class="fas fa-arrow-<?= $crescimento_despesa >= 0 ? 'up' : 'down' ?>"></i>
                        <?= number_format(abs($crescimento_despesa), 1, ',', '.') ?>% vs mês anterior
                    </div>
                <?php endif; ?>
            </div>
            <div class="card">
                <h3>LUCRO LÍQUIDO</h3>
                <!-- Level Up: Indicador Colorido conforme o sinal do resultado -->
                <div class="value <?= $lucro_liquido >= 0 ? 'value-receita' : 'value-despesa' ?>">R$ <?= number_format($lucro_liquido, 2, ',', '.'); ?></div>
            </div>
            <div class="card">
                <h3>SALDO ATUAL</h3>
                <!-- Level Up: Indicador Colorido conforme o sinal do saldo -->
                <div class="value <?= $saldo_atual >= 0 ? 'value-receita' : 'value-despesa' ?>">R$ <?= number_format($saldo_atual, 2, ',', '.'); ?></div>
            </div>
        </section>

        <!-- KPIs Operacionais/Performance (complemento dos 8 indicadores obrigatórios) -->
        <section class="kpi-secondary-grid">
            <div class="card">
                <h3>TICKET MÉDIO</h3>
                <div class="value">R$ <?= number_format($ticket_medio, 2, ',', '.'); ?></div>
            </div>
            <div class="card">
                <h3>MAIOR RECEITA</h3>
                <div class="value">R$ <?= number_format($maior_receita, 2, ',', '.'); ?></div>
            </div>
            <div class="card">
                <h3>Nº DE RECEITAS</h3>
                <div class="value"><?= $numero_receitas ?></div>
            </div>
            <div class="card">
                <h3>Nº DE DESPESAS</h3>
                <div class="value"><?= $numero_despesas ?></div>
            </div>
        </section>

        <!-- Prioridade 2: Análise Visual de Contraste e Distribuição -->
        <section class="chart-row">
            <div class="chart-card">
                <h3><i class="fas fa-chart-simple"></i> Receita x Despesa</h3>
                <div class="chart-wrap"><canvas id="chartReceitaDespesa"></canvas></div>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-layer-group"></i> Receitas por Categoria</h3>
                <div class="chart-wrap">
                    <?php if (empty($grafico_cat_receitas)): ?>
                        <p class="chart-vazio">Sem receitas no período selecionado.</p>
                    <?php else: ?>
                        <canvas id="chartCatReceitas"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Prioridade 3: Tendências e Operações -->
        <section class="chart-row">
            <div class="chart-card">
                <h3><i class="fas fa-triangle-exclamation"></i> Despesas por Categoria</h3>
                <div class="chart-wrap">
                    <?php if (empty($grafico_cat_despesas)): ?>
                        <p class="chart-vazio">Sem despesas no período selecionado.</p>
                    <?php else: ?>
                        <canvas id="chartCatDespesas"></canvas>
                    <?php endif; ?>
                </div>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Evolução Mensal (Receitas)</h3>
                <div class="chart-wrap">
                    <?php if (empty($grafico_evolucao)): ?>
                        <p class="chart-vazio">Sem histórico no período selecionado.</p>
                    <?php else: ?>
                        <canvas id="chartEvolucao"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="chart-row">
            <div class="chart-card">
                <h3><i class="fas fa-wallet"></i> Formas de Pagamento</h3>
                <div class="chart-wrap">
                    <?php if (empty($grafico_formas_pagamento)): ?>
                        <p class="chart-vazio">Sem movimentações no período selecionado.</p>
                    <?php else: ?>
                        <canvas id="chartFormasPagamento"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Últimas Movimentações -->
        <section class="card">
            <h3 style="margin-bottom:14px; color:#1e3c72;">Últimas Movimentações</h3>
            <?php if (empty($ultimas_movimentacoes)): ?>
                <p class="chart-vazio">Nenhuma movimentação no período selecionado.</p>
            <?php else: ?>
                <div class="mov-table-wrap">
                    <table class="mov-table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimas_movimentacoes as $mov): ?>
                                <tr>
                                    <td data-label="Data"><?= !empty($mov['data']) ? date('d/m/Y', strtotime($mov['data'])) : '-' ?></td>
                                    <td data-label="Tipo">
                                        <span class="badge <?= $mov['tipo'] === 'Receita' ? 'badge-receita' : 'badge-despesa' ?>">
                                            <?= htmlspecialchars($mov['tipo']) ?>
                                        </span>
                                    </td>
                                    <td data-label="Descrição"><?= htmlspecialchars($mov['descricao']) ?></td>
                                    <td data-label="Valor" style="color: <?= $mov['tipo'] === 'Receita' ? '#16a34a' : '#dc2626' ?>; font-weight:600;">
                                        R$ <?= number_format((float)$mov['entrada'] + (float)$mov['saida'], 2, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <!-- Script de controle do menu lateral -->
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

    <!-- Gráficos (Sprint 09 - Chart.js) -->
    <script>
        const paletaCategorica = <?= json_encode($paleta_categorica) ?>;

        // Level Up: Gráficos Responsivos (redesenham ao redimensionar a janela/tela)
        Chart.defaults.responsive = true;
        Chart.defaults.maintainAspectRatio = false;

        // Opções compartilhadas pelos gráficos de barra horizontal por categoria
        const opcoesBarraHorizontal = {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, grid: { color: '#e1e0d9' } },
                y: { grid: { display: false } }
            }
        };

        // Gráfico 1: Receita x Despesa
        new Chart(document.getElementById('chartReceitaDespesa'), {
            type: 'bar',
            data: {
                labels: ['Receita', 'Despesa'],
                datasets: [{
                    data: [
                        <?= (float)$grafico_receita_despesa['receita'] ?>,
                        <?= (float)$grafico_receita_despesa['despesa'] ?>
                    ],
                    backgroundColor: ['#16a34a', '#dc2626'],
                    borderRadius: 4,
                    maxBarThickness: 70
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e1e0d9' } },
                    x: { grid: { display: false } }
                }
            }
        });

        <?php if (!empty($grafico_cat_receitas)): ?>
        // Gráfico 2 (Distribuição): Receitas por Categoria, em pizza - mostra o
        // peso de cada fonte de faturamento sobre o total
        new Chart(document.getElementById('chartCatReceitas'), {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($grafico_cat_receitas, 'categoria')) ?>,
                datasets: [{
                    data: <?= json_encode(array_map('floatval', array_column($grafico_cat_receitas, 'total'))) ?>,
                    backgroundColor: paletaCategorica,
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                // Na pizza a categoria não aparece em eixo, então a legenda é obrigatória.
                // Em tela estreita ela vai para baixo, para não espremer o gráfico.
                plugins: {
                    legend: {
                        position: window.innerWidth < 768 ? 'bottom' : 'right',
                        labels: { boxWidth: 12, padding: 12 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = total > 0 ? (ctx.parsed / total * 100).toFixed(1) : 0;
                                return ctx.label + ': R$ ' + ctx.parsed.toLocaleString('pt-BR', { minimumFractionDigits: 2 }) + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>

        <?php if (!empty($grafico_cat_despesas)): ?>
        // Gráfico 3: Despesas por Categoria (identificar gargalos financeiros)
        new Chart(document.getElementById('chartCatDespesas'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($grafico_cat_despesas, 'categoria')) ?>,
                datasets: [{
                    data: <?= json_encode(array_map('floatval', array_column($grafico_cat_despesas, 'total'))) ?>,
                    backgroundColor: paletaCategorica,
                    borderRadius: 4
                }]
            },
            options: opcoesBarraHorizontal
        });
        <?php endif; ?>

        <?php if (!empty($grafico_evolucao)): ?>
        // Gráfico 4: Evolução Mensal das Receitas (linha, hue único)
        new Chart(document.getElementById('chartEvolucao'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($grafico_evolucao, 'mes_label')) ?>,
                datasets: [{
                    label: 'Receitas',
                    data: <?= json_encode(array_map('floatval', array_column($grafico_evolucao, 'total'))) ?>,
                    borderColor: '#2a78d6',
                    backgroundColor: 'rgba(42, 120, 214, 0.12)',
                    borderWidth: 2,
                    pointRadius: 4,
                    fill: true,
                    tension: 0.25
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e1e0d9' } },
                    x: { grid: { display: false } }
                }
            }
        });
        <?php endif; ?>

        <?php if (!empty($grafico_formas_pagamento)): ?>
        // Gráfico 5: Formas de Pagamento (contagem de uso)
        new Chart(document.getElementById('chartFormasPagamento'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($grafico_formas_pagamento, 'forma')) ?>,
                datasets: [{
                    data: <?= json_encode(array_map('intval', array_column($grafico_formas_pagamento, 'qtd'))) ?>,
                    backgroundColor: paletaCategorica,
                    borderRadius: 4
                }]
            },
            options: {
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#e1e0d9' } },
                    y: { grid: { display: false } }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
