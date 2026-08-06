<?php
// executivo.php — Dashboard Executivo (Sprint 10)
// Inteligência Estratégica para Tomada de Decisão
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: index.php");
    exit;
}

require_once "config/conexao.php";

$usuario_id   = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;
$usuario_nome = $_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? 'Usuário';

// ─── Filtros ────────────────────────────────────────────────────────────────
$f_inicio    = isset($_GET['f_data_inicio']) ? trim($_GET['f_data_inicio']) : '';
$f_fim       = isset($_GET['f_data_fim'])    ? trim($_GET['f_data_fim'])    : '';
$f_categoria = isset($_GET['f_categoria'])   ? trim($_GET['f_categoria'])   : '';
$f_periodo   = isset($_GET['f_periodo'])     ? trim($_GET['f_periodo'])     : '';

// Quick-select: sobrescreve datas
if ($f_periodo === 'hoje') {
    $f_inicio = $f_fim = date('Y-m-d');
} elseif ($f_periodo === 'mes') {
    $f_inicio = date('Y-m-01');
    $f_fim    = date('Y-m-t');
} elseif ($f_periodo === 'ano') {
    $f_inicio = date('Y-01-01');
    $f_fim    = date('Y-12-31');
}

$tem_filtro_periodo = ($f_inicio !== '' || $f_fim !== '');
$tem_filtro_ativo   = ($tem_filtro_periodo || $f_categoria !== '');

function filtroSql($col_data, $inicio, $fim, $categoria, $sfx = '') {
    $s = '';
    if ($inicio !== '')    $s .= " AND {$col_data} >= :data_inicio{$sfx}";
    if ($fim !== '')       $s .= " AND {$col_data} <= :data_fim{$sfx}";
    if ($categoria !== '') $s .= " AND categoria_id = :categoria_id{$sfx}";
    return $s;
}
function filtroParams($inicio, $fim, $categoria, $sfx = '') {
    $p = [];
    if ($inicio !== '')    $p['data_inicio'.$sfx] = $inicio;
    if ($fim !== '')       $p['data_fim'.$sfx]    = $fim;
    if ($categoria !== '') $p['categoria_id'.$sfx] = $categoria;
    return $p;
}

// ─── Valores padrão ─────────────────────────────────────────────────────────
$receita_total = $despesa_total = $maior_receita = $maior_despesa = 0.0;
$numero_receitas = $numero_despesas = 0;
$qtd_clientes = $qtd_fornecedores = 0;
$categorias = [];
$grafico_evolucao_r = $grafico_evolucao_d = [];
$grafico_cat_receitas = $grafico_cat_despesas = [];
$grafico_forma_receitas = [];
$ranking_clientes = $ranking_categorias = [];
$metas_lista = [];
// Comparativos
$rec_mes_atual = $rec_mes_ant = $desp_mes_atual = $desp_mes_ant = 0.0;
$rec_ano_atual = $rec_ano_ant = 0.0;

if ($usuario_id) {
    try {
        // Categorias (filtro)
        $st = $conexao->prepare("SELECT id, nome, tipo FROM categorias WHERE usuario_id=:uid AND (ativo='S' OR ativo='1') ORDER BY tipo, nome");
        $st->execute(['uid' => $usuario_id]);
        $categorias = $st->fetchAll();

        // KPI 1+2+6+7: receita/despesa totais, maior, qtd
        $st = $conexao->prepare("SELECT COALESCE(SUM(valor),0) total, COUNT(*) qtd, COALESCE(MAX(valor),0) maior FROM receitas WHERE usuario_id=:uid AND ativo='S'" . filtroSql('data_recebimento', $f_inicio, $f_fim, $f_categoria));
        $st->execute(array_merge(['uid'=>$usuario_id], filtroParams($f_inicio,$f_fim,$f_categoria)));
        $r = $st->fetch();
        $receita_total   = (float)$r['total'];
        $numero_receitas = (int)$r['qtd'];
        $maior_receita   = (float)$r['maior'];

        $st = $conexao->prepare("SELECT COALESCE(SUM(valor),0) total, COUNT(*) qtd, COALESCE(MAX(valor),0) maior FROM despesas WHERE usuario_id=:uid AND ativo='S'" . filtroSql('data_pagamento', $f_inicio, $f_fim, $f_categoria));
        $st->execute(array_merge(['uid'=>$usuario_id], filtroParams($f_inicio,$f_fim,$f_categoria)));
        $d = $st->fetch();
        $despesa_total   = (float)$d['total'];
        $numero_despesas = (int)$d['qtd'];
        $maior_despesa   = (float)$d['maior'];

        // KPI 8+9: clientes e fornecedores
        $qtd_clientes    = (int)$conexao->query("SELECT COUNT(*) FROM clientes WHERE ativo='S'")->fetchColumn();
        $qtd_fornecedores= (int)$conexao->query("SELECT COUNT(*) FROM fornecedores WHERE ativo='S'")->fetchColumn();

        // Comparativo mensal (não respeita filtro de período)
        $filtro_cat_sql = $f_categoria !== '' ? " AND categoria_id=:categoria_id" : "";
        $filtro_cat_par = $f_categoria !== '' ? ['categoria_id'=>$f_categoria] : [];

        $st = $conexao->prepare("SELECT
            SUM(CASE WHEN DATE_FORMAT(data_recebimento,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m') THEN valor ELSE 0 END) mes_atual,
            SUM(CASE WHEN DATE_FORMAT(data_recebimento,'%Y-%m')=DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m') THEN valor ELSE 0 END) mes_ant
            FROM receitas WHERE usuario_id=:uid AND ativo='S' {$filtro_cat_sql}");
        $st->execute(array_merge(['uid'=>$usuario_id], $filtro_cat_par));
        $rc = $st->fetch();
        $rec_mes_atual = (float)$rc['mes_atual'];
        $rec_mes_ant   = (float)$rc['mes_ant'];

        $st = $conexao->prepare("SELECT
            SUM(CASE WHEN DATE_FORMAT(data_pagamento,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m') THEN valor ELSE 0 END) mes_atual,
            SUM(CASE WHEN DATE_FORMAT(data_pagamento,'%Y-%m')=DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 1 MONTH),'%Y-%m') THEN valor ELSE 0 END) mes_ant
            FROM despesas WHERE usuario_id=:uid AND ativo='S' {$filtro_cat_sql}");
        $st->execute(array_merge(['uid'=>$usuario_id], $filtro_cat_par));
        $dc = $st->fetch();
        $desp_mes_atual = (float)$dc['mes_atual'];
        $desp_mes_ant   = (float)$dc['mes_ant'];

        // Comparativo anual
        $st = $conexao->prepare("SELECT
            SUM(CASE WHEN YEAR(data_recebimento)=YEAR(CURDATE()) THEN valor ELSE 0 END) ano_atual,
            SUM(CASE WHEN YEAR(data_recebimento)=YEAR(CURDATE())-1 THEN valor ELSE 0 END) ano_ant
            FROM receitas WHERE usuario_id=:uid AND ativo='S' {$filtro_cat_sql}");
        $st->execute(array_merge(['uid'=>$usuario_id], $filtro_cat_par));
        $ry = $st->fetch();
        $rec_ano_atual = (float)$ry['ano_atual'];
        $rec_ano_ant   = (float)$ry['ano_ant'];

        // Gráfico: Evolução mensal Receita x Despesa (12 meses)
        $sql_ev = "SELECT DATE_FORMAT(data_recebimento,'%Y-%m') mes, SUM(valor) total FROM receitas WHERE usuario_id=:uid AND ativo='S'";
        if ($tem_filtro_periodo) {
            $sql_ev .= filtroSql('data_recebimento', $f_inicio, $f_fim, $f_categoria);
        } else {
            $sql_ev .= " AND data_recebimento >= DATE_SUB(CURDATE(),INTERVAL 12 MONTH)";
            if ($f_categoria !== '') $sql_ev .= " AND categoria_id=:categoria_id";
        }
        $sql_ev .= " GROUP BY mes ORDER BY mes ASC";
        $st = $conexao->prepare($sql_ev);
        $st->execute(array_merge(['uid'=>$usuario_id], filtroParams($tem_filtro_periodo?$f_inicio:'', $tem_filtro_periodo?$f_fim:'', $f_categoria)));
        $grafico_evolucao_r = $st->fetchAll();

        $sql_evd = "SELECT DATE_FORMAT(data_pagamento,'%Y-%m') mes, SUM(valor) total FROM despesas WHERE usuario_id=:uid AND ativo='S'";
        if ($tem_filtro_periodo) {
            $sql_evd .= filtroSql('data_pagamento', $f_inicio, $f_fim, $f_categoria);
        } else {
            $sql_evd .= " AND data_pagamento >= DATE_SUB(CURDATE(),INTERVAL 12 MONTH)";
            if ($f_categoria !== '') $sql_evd .= " AND categoria_id=:categoria_id";
        }
        $sql_evd .= " GROUP BY mes ORDER BY mes ASC";
        $st = $conexao->prepare($sql_evd);
        $st->execute(array_merge(['uid'=>$usuario_id], filtroParams($tem_filtro_periodo?$f_inicio:'', $tem_filtro_periodo?$f_fim:'', $f_categoria)));
        $grafico_evolucao_d = $st->fetchAll();

        // Gráfico: Receita por Categoria (doughnut)
        $st = $conexao->prepare("SELECT COALESCE(c.nome,'Sem Categoria') categoria, SUM(r.valor) total FROM receitas r LEFT JOIN categorias c ON r.categoria_id=c.id WHERE r.usuario_id=:uid AND r.ativo='S'" . filtroSql('r.data_recebimento',$f_inicio,$f_fim,'') . ($f_categoria!==''?" AND r.categoria_id=:categoria_id":"") . " GROUP BY categoria ORDER BY total DESC");
        $st->execute(array_merge(['uid'=>$usuario_id], filtroParams($f_inicio,$f_fim,$f_categoria)));
        $grafico_cat_receitas = $st->fetchAll();

        // Gráfico: Despesa por Categoria (bar horizontal, top 10)
        $st = $conexao->prepare("SELECT COALESCE(c.nome,'Sem Categoria') categoria, SUM(d.valor) total FROM despesas d LEFT JOIN categorias c ON d.categoria_id=c.id WHERE d.usuario_id=:uid AND d.ativo='S'" . filtroSql('d.data_pagamento',$f_inicio,$f_fim,'') . ($f_categoria!==''?" AND d.categoria_id=:categoria_id":"") . " GROUP BY categoria ORDER BY total DESC LIMIT 10");
        $st->execute(array_merge(['uid'=>$usuario_id], filtroParams($f_inicio,$f_fim,$f_categoria)));
        $grafico_cat_despesas = $st->fetchAll();

        // Gráfico: Receita por Forma de Pagamento (pie)
        $st = $conexao->prepare("SELECT COALESCE(fp.descricao,'Não informado') forma, COUNT(*) qtd FROM receitas r LEFT JOIN formas_pagamento fp ON r.forma_pagamento_id=fp.id WHERE r.usuario_id=:uid AND r.ativo='S'" . filtroSql('r.data_recebimento',$f_inicio,$f_fim,'') . ($f_categoria!==''?" AND r.categoria_id=:categoria_id":"") . " GROUP BY forma ORDER BY qtd DESC");
        $st->execute(array_merge(['uid'=>$usuario_id], filtroParams($f_inicio,$f_fim,$f_categoria)));
        $grafico_forma_receitas = $st->fetchAll();

        // Ranking: Clientes por receita (top 10)
        $st = $conexao->prepare("SELECT COALESCE(cl.nome,'Cliente não informado') nome, SUM(r.valor) total FROM receitas r LEFT JOIN clientes cl ON r.cliente_id=cl.id WHERE r.usuario_id=:uid AND r.ativo='S'" . filtroSql('r.data_recebimento',$f_inicio,$f_fim,'') . ($f_categoria!==''?" AND r.categoria_id=:categoria_id":"") . " GROUP BY r.cliente_id ORDER BY total DESC LIMIT 10");
        $st->execute(array_merge(['uid'=>$usuario_id], filtroParams($f_inicio,$f_fim,$f_categoria)));
        $ranking_clientes = $st->fetchAll();

        // Ranking: Categorias top 10 por receita total
        $st = $conexao->prepare("SELECT COALESCE(c.nome,'Sem Categoria') categoria, SUM(r.valor) total FROM receitas r LEFT JOIN categorias c ON r.categoria_id=c.id WHERE r.usuario_id=:uid AND r.ativo='S'" . filtroSql('r.data_recebimento',$f_inicio,$f_fim,'') . ($f_categoria!==''?" AND r.categoria_id=:categoria_id":"") . " GROUP BY r.categoria_id ORDER BY total DESC LIMIT 10");
        $st->execute(array_merge(['uid'=>$usuario_id], filtroParams($f_inicio,$f_fim,$f_categoria)));
        $ranking_categorias = $st->fetchAll();

        // Metas do banco
        $st = $conexao->query("SELECT * FROM metas ORDER BY id ASC");
        $metas_lista = $st->fetchAll();

    } catch (PDOException $e) {
        // mantém zeros; evita quebrar a tela
    }
}

// ─── KPIs derivados ────────────────────────────────────────────────────────
$lucro_liquido = $receita_total - $despesa_total;
$saldo_atual   = $lucro_liquido;
$ticket_medio  = $numero_receitas > 0 ? $receita_total / $numero_receitas : 0.0;
$margem_lucro  = $receita_total > 0 ? ($lucro_liquido / $receita_total) * 100 : 0.0;

// ─── Variações percentuais ─────────────────────────────────────────────────
function varPct($atual, $anterior) {
    if ($anterior == 0) return $atual > 0 ? 100 : 0;
    return (($atual - $anterior) / $anterior) * 100;
}
$var_rec_mes = varPct($rec_mes_atual, $rec_mes_ant);
$var_desp_mes = varPct($desp_mes_atual, $desp_mes_ant);
$var_rec_ano  = varPct($rec_ano_atual, $rec_ano_ant);

// ─── Metas: cálculo dinâmico do realizado ─────────────────────────────────
foreach ($metas_lista as &$meta) {
    $vm = (float)$meta['valor_meta'];
    if ($meta['tipo'] === 'Receita') {
        // Para Lucro: usa lucro; para Receita genérica: usa receita_total
        if (stripos($meta['descricao'], 'lucro') !== false) {
            $realizado = $lucro_liquido;
        } else {
            $realizado = $receita_total;
        }
        $pct = $vm > 0 ? ($realizado / $vm) * 100 : 0;
    } else {
        // Despesa: meta é um limite — abaixo do limite é bom
        $realizado = $despesa_total;
        // 100% = meta atingida (gastou exatamente o limite), >100% = ruim
        $pct = $vm > 0 ? ($realizado / $vm) * 100 : 0;
    }
    $meta['_realizado'] = $realizado;
    $meta['_pct']       = min($pct, 999);

    // Semáforo
    if ($meta['tipo'] === 'Despesa') {
        // Para despesa, abaixo do limite = bom
        if ($pct <= 100)     $meta['_semaforo'] = 'verde';
        elseif ($pct <= 130) $meta['_semaforo'] = 'amarelo';
        else                 $meta['_semaforo'] = 'vermelho';
    } else {
        if ($pct >= 100)     $meta['_semaforo'] = 'verde';
        elseif ($pct >= 70)  $meta['_semaforo'] = 'amarelo';
        else                 $meta['_semaforo'] = 'vermelho';
    }

    $semLabel = ['verde'=>'Atingida','amarelo'=>'Atenção','vermelho'=>'Crítica'];
    $meta['_semaforo_label'] = $semLabel[$meta['_semaforo']];
}
unset($meta);

// ─── SWOT Dinâmica ─────────────────────────────────────────────────────────
$forcas = $fraquezas = $oportunidades = $ameacas = [];

// Forças
if ($margem_lucro >= 20) $forcas[] = "Margem de lucro saudável (" . number_format($margem_lucro,1,',','.') . "%)";
if ($var_rec_mes > 0)    $forcas[] = "Crescimento de receitas no mês (+" . number_format($var_rec_mes,1,',','.') . "%)";
if ($ticket_medio > 0)   $forcas[] = "Ticket médio de R$\u{00A0}" . number_format($ticket_medio,2,',','.');
if ($qtd_clientes > 0)   $forcas[] = "Base de {$qtd_clientes} cliente(s) ativo(s)";
if (empty($forcas))      $forcas[] = "Controle financeiro sistematizado";

// Fraquezas
if ($margem_lucro < 10 && $margem_lucro >= 0) $fraquezas[] = "Margem de lucro abaixo de 10% (" . number_format($margem_lucro,1,',','.') . "%)";
if ($lucro_liquido < 0)    $fraquezas[] = "Resultado negativo no período";
if ($qtd_clientes == 0)    $fraquezas[] = "Nenhum cliente cadastrado no sistema";
if ($numero_receitas == 0) $fraquezas[] = "Nenhuma receita registrada no período";
if (empty($fraquezas))     $fraquezas[] = "Dependência de poucas fontes de receita";

// Oportunidades
if ($var_rec_mes > 10) $oportunidades[] = "Forte crescimento mensal de receitas (+" . number_format($var_rec_mes,1,',','.') . "%)";
if ($var_rec_ano > 0)  $oportunidades[] = "Crescimento anual positivo (+" . number_format($var_rec_ano,1,',','.') . "%)";
$oportunidades[] = "Expansão para novos mercados e categorias";
if ($qtd_clientes > 0) $oportunidades[] = "Fidelização da base de clientes existente";

// Ameaças
if ($var_desp_mes > $var_rec_mes && $var_desp_mes > 0) $ameacas[] = "Despesas crescendo mais rápido que receitas";
if ($margem_lucro > 0 && $margem_lucro < 15) $ameacas[] = "Margem de lucro em zona de atenção";
if ($despesa_total > $receita_total) $ameacas[] = "Despesas superam receitas no período";
$ameacas[] = "Sazonalidade pode impactar o faturamento";
if (empty($ameacas) || (count($ameacas)==1 && strpos($ameacas[0],'Sazonalidade')!==false)) {
    $ameacas[] = "Aumento de custos operacionais fixos";
}

// ─── Dados de gráficos: merge meses receita+despesa ───────────────────────
$meses_map = [];
foreach ($grafico_evolucao_r as $p) {
    $dt = DateTime::createFromFormat('Y-m-d', $p['mes'].'-01');
    $meses_map[$p['mes']] = ['label'=> $dt ? $dt->format('m/Y') : $p['mes'], 'receita'=>(float)$p['total'], 'despesa'=>0.0];
}
foreach ($grafico_evolucao_d as $p) {
    if (!isset($meses_map[$p['mes']])) {
        $dt = DateTime::createFromFormat('Y-m-d', $p['mes'].'-01');
        $meses_map[$p['mes']] = ['label'=> $dt ? $dt->format('m/Y') : $p['mes'], 'receita'=>0.0, 'despesa'=>0.0];
    }
    $meses_map[$p['mes']]['despesa'] = (float)$p['total'];
}
ksort($meses_map);
$ev_labels   = array_column($meses_map,'label');
$ev_receitas = array_column($meses_map,'receita');
$ev_despesas = array_column($meses_map,'despesa');
$ev_lucros   = array_map(fn($r,$d)=>$r-$d, $ev_receitas, $ev_despesas);

$paleta = ['#3b82f6','#eb6834','#16a34a','#eda100','#e87ba4','#a78bfa','#06b6d4','#f43f5e','#84cc16','#f97316'];
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Executivo — 4 Union</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <link rel="stylesheet" href="assets/css/executivo.css?v=<?= time() ?>">
    <script>if(localStorage.getItem('sidebarCollapsed')==='true'){document.documentElement.classList.add('sidebar-collapsed-init');}</script>
</head>
<body>

<?php $sidebarActive = 'executivo'; $sidebarRoot = ''; include __DIR__ . '/partials/sidebar.php'; ?>

<!-- ══════════ MAIN ══════════ -->
<main class="main-content">

    <!-- HEADER -->
    <header class="exec-header">
        <div class="exec-header-left">
            <h1><i class="fas fa-rocket" style="color:#3b82f6;margin-right:8px;"></i>Dashboard Executivo</h1>
            <p>Olá, <strong><?= htmlspecialchars($usuario_nome) ?></strong> &mdash; Inteligência estratégica em tempo real</p>
        </div>
        <div class="exec-header-right">
            <span class="badge-refresh">
                <i class="fas fa-rotate"></i>
                Atualiza em <span id="refresh-timer">5m00s</span>
            </span>
            <?php if ($tem_filtro_ativo): ?>
                <a href="executivo.php" class="btn-limpar"><i class="fas fa-times"></i> Limpar Filtros</a>
            <?php endif; ?>
        </div>
    </header>

    <!-- FILTROS -->
    <div class="filtros-panel">
        <div class="filtros-quick">
            <a href="executivo.php?f_periodo=hoje&f_categoria=<?= urlencode($f_categoria) ?>" class="btn-quick <?= $f_periodo==='hoje'?'ativo':'' ?>">Hoje</a>
            <a href="executivo.php?f_periodo=mes&f_categoria=<?= urlencode($f_categoria) ?>"  class="btn-quick <?= $f_periodo==='mes'?'ativo':'' ?>">Este Mês</a>
            <a href="executivo.php?f_periodo=ano&f_categoria=<?= urlencode($f_categoria) ?>"  class="btn-quick <?= $f_periodo==='ano'?'ativo':'' ?>">Este Ano</a>
            <a href="executivo.php" class="btn-quick" style="<?= !$tem_filtro_ativo?'opacity:.4;cursor:default;':'' ?>">Tudo</a>
        </div>
        <form action="executivo.php" method="GET" class="filtros-form">
            <div>
                <label>Início</label>
                <input type="date" name="f_data_inicio" value="<?= htmlspecialchars($f_inicio) ?>" max="9999-12-31">
            </div>
            <div>
                <label>Fim</label>
                <input type="date" name="f_data_fim" value="<?= htmlspecialchars($f_fim) ?>" max="9999-12-31">
            </div>
            <div>
                <label>Categoria</label>
                <select name="f_categoria">
                    <option value="">Todas</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $f_categoria==$cat['id']?'selected':'' ?>>
                            <?= htmlspecialchars($cat['nome']) ?> (<?= htmlspecialchars($cat['tipo']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-filtrar"><i class="fas fa-search"></i> Aplicar</button>
        </form>
    </div>

    <?php if ($tem_filtro_ativo): ?>
    <div class="filtro-ativo-aviso">
        <i class="fas fa-circle-info"></i>
        Exibindo dados filtrados.
        <a href="executivo.php">Limpar filtros</a>
    </div>
    <?php endif; ?>

    <!-- ── KPIs PRIMÁRIOS (5 cartões) ── -->
    <p class="section-title"><i class="fas fa-gauge-high"></i> Indicadores Estratégicos</p>
    <div class="kpi-grid-primary">

        <div class="kpi-card">
            <div class="kpi-label"><i class="fas fa-arrow-up-right-dots" style="color:#16a34a;"></i> Receita Total</div>
            <div class="kpi-value">R$&nbsp;<?= number_format($receita_total,2,',','.') ?></div>
            <?php if (!$tem_filtro_periodo && $rec_mes_ant > 0): ?>
            <span class="kpi-badge <?= $var_rec_mes>=0?'up':'down' ?>">
                <i class="fas fa-arrow-<?= $var_rec_mes>=0?'up':'down' ?>"></i>
                <?= number_format(abs($var_rec_mes),1,',','.') ?>% vs mês ant.
            </span>
            <?php endif; ?>
        </div>

        <div class="kpi-card">
            <div class="kpi-label"><i class="fas fa-sack-dollar" style="color:<?= $lucro_liquido>=0?'#16a34a':'#dc2626'; ?>;"></i> Lucro Líquido</div>
            <div class="kpi-value <?= $lucro_liquido>=0?'positivo':'negativo' ?>">R$&nbsp;<?= number_format($lucro_liquido,2,',','.') ?></div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label"><i class="fas fa-percent" style="color:#d97706;"></i> Margem de Lucro</div>
            <div class="kpi-value <?= $margem_lucro>=20?'positivo':($margem_lucro<0?'negativo':'neutro') ?>">
                <?= number_format($margem_lucro,1,',','.') ?>%
            </div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label"><i class="fas fa-receipt" style="color:#7c3aed;"></i> Ticket Médio</div>
            <div class="kpi-value">R$&nbsp;<?= number_format($ticket_medio,2,',','.') ?></div>
        </div>

        <div class="kpi-card">
            <div class="kpi-label"><i class="fas fa-wallet" style="color:#0284c7;"></i> Saldo Atual</div>
            <div class="kpi-value <?= $saldo_atual>=0?'positivo':'negativo' ?>">R$&nbsp;<?= number_format($saldo_atual,2,',','.') ?></div>
        </div>
    </div>

    <!-- ── KPIs SECUNDÁRIOS (5 cartões) ── -->
    <div class="kpi-grid-secondary">
        <div class="kpi-card secondary">
            <div class="kpi-label"><i class="fas fa-arrow-up" style="color:#16a34a;"></i> Maior Receita</div>
            <div class="kpi-value">R$&nbsp;<?= number_format($maior_receita,2,',','.') ?></div>
        </div>
        <div class="kpi-card secondary">
            <div class="kpi-label"><i class="fas fa-arrow-down" style="color:#dc2626;"></i> Maior Despesa</div>
            <div class="kpi-value">R$&nbsp;<?= number_format($maior_despesa,2,',','.') ?></div>
        </div>
        <div class="kpi-card secondary">
            <div class="kpi-label"><i class="fas fa-users" style="color:#2563eb;"></i> Qtd de Clientes</div>
            <div class="kpi-value"><?= $qtd_clientes ?></div>
        </div>
        <div class="kpi-card secondary">
            <div class="kpi-label"><i class="fas fa-handshake" style="color:#ea580c;"></i> Qtd de Fornecedores</div>
            <div class="kpi-value"><?= $qtd_fornecedores ?></div>
        </div>
        <div class="kpi-card secondary">
            <div class="kpi-label"><i class="fas fa-hashtag" style="color:#65a30d;"></i> Transações</div>
            <div class="kpi-value"><?= $numero_receitas + $numero_despesas ?></div>
        </div>
    </div>

    <!-- ── GRÁFICOS TEMPORAIS ── -->
    <p class="section-title"><i class="fas fa-chart-line"></i> Evolução no Tempo (últimos 12 meses)</p>
    <div class="chart-row" style="margin-bottom:28px;">
        <div class="chart-card">
            <h3><i class="fas fa-chart-line"></i> Receita x Despesa por Mês</h3>
            <div class="chart-wrap">
                <?php if (empty($meses_map)): ?>
                    <p class="chart-vazio">Sem dados no período.</p>
                <?php else: ?>
                    <canvas id="chartEvolucao"></canvas>
                <?php endif; ?>
            </div>
        </div>
        <div class="chart-card">
            <h3><i class="fas fa-chart-area"></i> Evolução do Lucro</h3>
            <div class="chart-wrap">
                <?php if (empty($meses_map)): ?>
                    <p class="chart-vazio">Sem dados no período.</p>
                <?php else: ?>
                    <canvas id="chartLucro"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── COMPARATIVOS ── -->
    <p class="section-title"><i class="fas fa-arrows-left-right"></i> Comparativos de Período</p>
    <div class="comparativo-row" style="margin-bottom:28px;">
        <div class="comparativo-card">
            <h3><i class="fas fa-calendar-day"></i> Comparativo Mensal — Receitas</h3>
            <div class="comp-row">
                <span class="comp-label"><?= date('m/Y') ?> (mês atual)</span>
                <span class="comp-value">R$&nbsp;<?= number_format($rec_mes_atual,2,',','.') ?></span>
            </div>
            <div class="comp-row">
                <span class="comp-label"><?= date('m/Y', strtotime('-1 month')) ?> (mês anterior)</span>
                <span class="comp-value">R$&nbsp;<?= number_format($rec_mes_ant,2,',','.') ?></span>
            </div>
            <div class="comp-variacao <?= $var_rec_mes>=0?'positiva':($var_rec_mes<0?'negativa':'neutra') ?>">
                <span>Variação</span>
                <span><?= $var_rec_mes>=0?'+':'' ?><?= number_format($var_rec_mes,1,',','.') ?>% <?= $var_rec_mes>=0?'↑':'↓' ?></span>
            </div>
        </div>
        <div class="comparativo-card">
            <h3><i class="fas fa-calendar-year"></i> Comparativo Anual — Receitas</h3>
            <div class="comp-row">
                <span class="comp-label"><?= date('Y') ?> (ano atual)</span>
                <span class="comp-value">R$&nbsp;<?= number_format($rec_ano_atual,2,',','.') ?></span>
            </div>
            <div class="comp-row">
                <span class="comp-label"><?= date('Y')-1 ?> (ano anterior)</span>
                <span class="comp-value">R$&nbsp;<?= number_format($rec_ano_ant,2,',','.') ?></span>
            </div>
            <div class="comp-variacao <?= $var_rec_ano>=0?'positiva':($var_rec_ano<0?'negativa':'neutra') ?>">
                <span>Variação</span>
                <span><?= $var_rec_ano>=0?'+':'' ?><?= number_format($var_rec_ano,1,',','.') ?>% <?= $var_rec_ano>=0?'↑':'↓' ?></span>
            </div>
        </div>
    </div>

    <!-- ── RANKINGS ── -->
    <p class="section-title"><i class="fas fa-trophy"></i> Rankings (Top 10)</p>
    <div class="chart-row" style="margin-bottom:16px;">
        <div class="chart-card">
            <h3><i class="fas fa-users"></i> Ranking de Clientes por Receita</h3>
            <div class="chart-wrap">
                <?php if (empty($ranking_clientes)): ?>
                    <p class="chart-vazio">Nenhum cliente com receitas associadas.</p>
                <?php else: ?>
                    <canvas id="chartRankingClientes"></canvas>
                <?php endif; ?>
            </div>
        </div>
        <div class="chart-card">
            <h3><i class="fas fa-tags"></i> Ranking de Categorias por Receita</h3>
            <div class="chart-wrap">
                <?php if (empty($ranking_categorias)): ?>
                    <p class="chart-vazio">Nenhuma categoria com receitas no período.</p>
                <?php else: ?>
                    <canvas id="chartRankingCategorias"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── DISTRIBUIÇÃO ── -->
    <p class="section-title"><i class="fas fa-chart-pie"></i> Distribuição e Proporção</p>
    <div class="chart-row" style="margin-bottom:28px;">
        <div class="chart-card">
            <h3><i class="fas fa-layer-group"></i> Receita por Categoria</h3>
            <div class="chart-wrap">
                <?php if (empty($grafico_cat_receitas)): ?>
                    <p class="chart-vazio">Sem receitas no período.</p>
                <?php else: ?>
                    <canvas id="chartCatReceitas"></canvas>
                <?php endif; ?>
            </div>
        </div>
        <div class="chart-card">
            <h3><i class="fas fa-wallet"></i> Receitas por Forma de Pagamento</h3>
            <div class="chart-wrap">
                <?php if (empty($grafico_forma_receitas)): ?>
                    <p class="chart-vazio">Sem dados de forma de pagamento.</p>
                <?php else: ?>
                    <canvas id="chartFormas"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Despesas por categoria -->
    <div class="chart-row single" style="margin-bottom:28px;">
        <div class="chart-card">
            <h3><i class="fas fa-triangle-exclamation"></i> Despesas por Categoria (Top 10)</h3>
            <div class="chart-wrap" style="height:280px;">
                <?php if (empty($grafico_cat_despesas)): ?>
                    <p class="chart-vazio">Sem despesas no período.</p>
                <?php else: ?>
                    <canvas id="chartCatDespesas"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── ESTRATÉGIA: METAS + SWOT ── -->
    <p class="section-title"><i class="fas fa-bullseye"></i> Inteligência Estratégica</p>
    <div class="estrategia-row">

        <!-- Metas -->
        <div class="metas-card">
            <h3><i class="fas fa-traffic-light"></i> Painel de Metas (Semáforos)</h3>
            <?php if (empty($metas_lista)): ?>
                <p style="color:var(--text-muted);font-size:0.8rem;">Nenhuma meta cadastrada. Execute o script <code>database/03_seed_metas_e_clientes.sql</code>.</p>
            <?php else: ?>
            <table class="metas-table">
                <thead>
                    <tr>
                        <th>Indicador</th>
                        <th>Meta</th>
                        <th>Atual</th>
                        <th>Situação</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($metas_lista as $meta): ?>
                    <tr>
                        <td><?= htmlspecialchars($meta['descricao']) ?></td>
                        <td class="meta-valor">R$&nbsp;<?= number_format((float)$meta['valor_meta'],2,',','.') ?></td>
                        <td class="meta-valor">R$&nbsp;<?= number_format($meta['_realizado'],2,',','.') ?></td>
                        <td>
                            <span class="semaforo <?= $meta['_semaforo'] ?>">
                                ● <?= $meta['_semaforo_label'] ?>
                            </span>
                            <div class="meta-progress-bar">
                                <div class="meta-progress-fill <?= $meta['_semaforo'] ?>"
                                     style="width:<?= min((float)$meta['_pct'],100) ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- SWOT -->
        <div class="swot-card">
            <h3><i class="fas fa-chess"></i> Análise SWOT Dinâmica</h3>
            <div class="swot-matrix">
                <div class="swot-quadrant forcas">
                    <div class="swot-quadrant-title">Forças (Strengths)</div>
                    <?php foreach ($forcas as $item): ?>
                        <div class="swot-item"><?= htmlspecialchars($item) ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="swot-quadrant fraquezas">
                    <div class="swot-quadrant-title">Fraquezas (Weaknesses)</div>
                    <?php foreach ($fraquezas as $item): ?>
                        <div class="swot-item"><?= htmlspecialchars($item) ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="swot-quadrant oportunidades">
                    <div class="swot-quadrant-title">Oportunidades (Opportunities)</div>
                    <?php foreach ($oportunidades as $item): ?>
                        <div class="swot-item"><?= htmlspecialchars($item) ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="swot-quadrant ameacas">
                    <div class="swot-quadrant-title">Ameaças (Threats)</div>
                    <?php foreach ($ameacas as $item): ?>
                        <div class="swot-item"><?= htmlspecialchars($item) ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</main>

<script>
// ── Sidebar toggle ───────────────────────────────────────────────────────────
function toggleSidebar() {
    document.documentElement.classList.remove('sidebar-collapsed-init');
    document.body.classList.toggle('sidebar-collapsed');
    localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed') ? 'true' : 'false');
}
if (localStorage.getItem('sidebarCollapsed') === 'true') document.body.classList.add('sidebar-collapsed');

// ── Auto-refresh countdown (5 min) ──────────────────────────────────────────
let countdown = 300;
const timerEl = document.getElementById('refresh-timer');
setInterval(() => {
    countdown--;
    const m = Math.floor(countdown / 60), s = countdown % 60;
    if (timerEl) timerEl.textContent = m + 'm' + String(s).padStart(2,'0') + 's';
    if (countdown <= 0) location.reload();
}, 1000);

// ── Chart.js config ──────────────────────────────────────────────────────────
Chart.defaults.responsive = true;
Chart.defaults.maintainAspectRatio = false;
Chart.defaults.color = '#64748b';
Chart.defaults.borderColor = '#e2e8f0';

const paleta = <?= json_encode($paleta) ?>;

const gridColor  = '#e2e8f0';
const tickColor  = '#64748b';
const scalesDark = {
    x: { grid: { color: gridColor }, ticks: { color: tickColor } },
    y: { grid: { color: gridColor }, ticks: { color: tickColor }, beginAtZero: true }
};
const scalesDarkH = {
    x: { grid: { color: gridColor }, ticks: { color: tickColor }, beginAtZero: true },
    y: { grid: { display: false }, ticks: { color: tickColor } }
};

<?php if (!empty($meses_map)): ?>
// Gráfico 1: Receita x Despesa — linha dupla
new Chart(document.getElementById('chartEvolucao'), {
    type: 'line',
    data: {
        labels: <?= json_encode($ev_labels) ?>,
        datasets: [
            {
                label: 'Receita',
                data: <?= json_encode($ev_receitas) ?>,
                borderColor: '#16a34a',
                backgroundColor: 'rgba(22,163,74,0.1)',
                borderWidth: 2, pointRadius: 4, fill: true, tension: 0.3
            },
            {
                label: 'Despesa',
                data: <?= json_encode($ev_despesas) ?>,
                borderColor: '#dc2626',
                backgroundColor: 'rgba(220,38,38,0.08)',
                borderWidth: 2, pointRadius: 4, fill: true, tension: 0.3
            }
        ]
    },
    options: {
        plugins: { legend: { labels: { color: '#64748b', boxWidth: 12 } } },
        scales: scalesDark
    }
});

// Gráfico 2: Evolução do Lucro — área
new Chart(document.getElementById('chartLucro'), {
    type: 'line',
    data: {
        labels: <?= json_encode($ev_labels) ?>,
        datasets: [{
            label: 'Lucro',
            data: <?= json_encode($ev_lucros) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.15)',
            borderWidth: 2, pointRadius: 4, fill: true, tension: 0.3
        }]
    },
    options: {
        plugins: { legend: { display: false } },
        scales: scalesDark
    }
});
<?php endif; ?>

<?php if (!empty($ranking_clientes)): ?>
new Chart(document.getElementById('chartRankingClientes'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($ranking_clientes,'nome')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('floatval', array_column($ranking_clientes,'total'))) ?>,
            backgroundColor: paleta,
            borderRadius: 4
        }]
    },
    options: { indexAxis:'y', plugins:{legend:{display:false}}, scales: scalesDarkH }
});
<?php endif; ?>

<?php if (!empty($ranking_categorias)): ?>
new Chart(document.getElementById('chartRankingCategorias'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($ranking_categorias,'categoria')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('floatval', array_column($ranking_categorias,'total'))) ?>,
            backgroundColor: paleta,
            borderRadius: 4
        }]
    },
    options: { indexAxis:'y', plugins:{legend:{display:false}}, scales: scalesDarkH }
});
<?php endif; ?>

<?php if (!empty($grafico_cat_receitas)): ?>
new Chart(document.getElementById('chartCatReceitas'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($grafico_cat_receitas,'categoria')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('floatval', array_column($grafico_cat_receitas,'total'))) ?>,
            backgroundColor: paleta,
            borderColor: '#ffffff',
            borderWidth: 2
        }]
    },
    options: {
        plugins: {
            legend: { position: window.innerWidth < 768 ? 'bottom' : 'right', labels: { color:'#64748b', boxWidth:12 } },
            tooltip: { callbacks: { label: ctx => {
                const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                const pct = total>0?(ctx.parsed/total*100).toFixed(1):0;
                return ctx.label+': R$ '+ctx.parsed.toLocaleString('pt-BR',{minimumFractionDigits:2})+' ('+pct+'%)';
            }}}
        }
    }
});
<?php endif; ?>

<?php if (!empty($grafico_forma_receitas)): ?>
new Chart(document.getElementById('chartFormas'), {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($grafico_forma_receitas,'forma')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($grafico_forma_receitas,'qtd'))) ?>,
            backgroundColor: paleta,
            borderColor: '#ffffff',
            borderWidth: 2
        }]
    },
    options: {
        plugins: { legend: { position:'right', labels: { color:'#64748b', boxWidth:12 } } }
    }
});
<?php endif; ?>

<?php if (!empty($grafico_cat_despesas)): ?>
new Chart(document.getElementById('chartCatDespesas'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($grafico_cat_despesas,'categoria')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('floatval', array_column($grafico_cat_despesas,'total'))) ?>,
            backgroundColor: paleta,
            borderRadius: 4
        }]
    },
    options: { indexAxis:'y', plugins:{legend:{display:false}}, scales: scalesDarkH }
});
<?php endif; ?>
</script>
</body>
</html>
