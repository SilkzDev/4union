<?php
// dashboard.php - Painel Principal
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

// Inicializa os indicadores zerados por padrão
$saldo_atual  = 0.00;
$receitas_mes = 0.00;
$despesas_mes = 0.00;

// Busca dados reais do banco para o usuário ativo
if ($usuario_id) {
    try {
        // 1. Busca Receitas (da nova tabela 'receitas')
        $stmtRec = $conexao->prepare("
            SELECT SUM(valor) AS total_receitas 
            FROM receitas 
            WHERE usuario_id = :usuario_id AND ativo = 'S'
        ");
        $stmtRec->execute(['usuario_id' => $usuario_id]);
        $resRec = $stmtRec->fetch(PDO::FETCH_ASSOC);
        $receitas_mes = (float)($resRec['total_receitas'] ?? 0.00);

        // 2. Busca Despesas (da tabela 'despesas')
        $stmtDesp = $conexao->prepare("
            SELECT SUM(valor) AS total_despesas
            FROM despesas
            WHERE usuario_id = :usuario_id AND ativo = 'S'
        ");
        $stmtDesp->execute(['usuario_id' => $usuario_id]);
        $resDesp = $stmtDesp->fetch(PDO::FETCH_ASSOC);
        $despesas_mes = (float)($resDesp['total_despesas'] ?? 0.00);

        // 3. Saldo Atual
        $saldo_atual = $receitas_mes - $despesas_mes;

    } catch (PDOException $e) {
        $saldo_atual  = 0.00;
        $receitas_mes = 0.00;
        $despesas_mes = 0.00;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - 4 Union</title>
    <!-- FontAwesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Arquivo CSS isolado -->
    <link rel="stylesheet" href="assets/css/dashboard.css">

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
                <a href="pages/receitas/index.php" class="btn-categoria-topo" style="background-color: #2e7d32; text-decoration: none; padding: 8px 14px; border-radius: 5px; color: #fff; font-size: 14px; font-weight: 600; margin-right: 8px;">
                    <i class="fas fa-plus-circle"></i> Nova Receita
                </a>
                <a href="pages/categorias/listar.php?nova=1" class="btn-categoria-topo">
                    <i class="fas fa-tag"></i> Adicionar Categorias
                </a>
            </div>
        </header>

        <!-- Indicadores Financeiros Reais -->
        <section class="cards-grid">
            <div class="card">
                <h3>SALDO ATUAL</h3>
                <div class="value">R$ <?= number_format($saldo_atual, 2, ',', '.'); ?></div>
            </div>
            <div class="card">
                <h3>RECEITAS DO MÊS</h3>
                <div class="value value-receita">R$ <?= number_format($receitas_mes, 2, ',', '.'); ?></div>
            </div>
            <div class="card">
                <h3>DESPESAS DO MÊS</h3>
                <div class="value value-despesa">R$ <?= number_format($despesas_mes, 2, ',', '.'); ?></div>
            </div>
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
    </script>
</body>
</html>