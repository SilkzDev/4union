<?php
// partials/sidebar.php — menu lateral compartilhado por todas as telas internas.
// A página que faz o include define, antes do include:
//   $sidebarActive = 'dashboard'|'executivo'|'receitas'|'despesas'|'fluxo_caixa'|'categorias'
//   $sidebarRoot   = '' (páginas na raiz) ou '../../' (páginas em pages/xxx/)

$sidebarActive = $sidebarActive ?? '';
$sidebarRoot   = $sidebarRoot ?? '';

$navItems = [
    'dashboard'   => ['href' => 'dashboard.php',                 'icon' => 'fa-home',                   'label' => 'Visão Geral'],
    'executivo'   => ['href' => 'executivo.php',                 'icon' => 'fa-rocket',                 'label' => 'Dashboard Executivo'],
    'receitas'    => ['href' => 'pages/receitas/listar.php',     'icon' => 'fa-hand-holding-dollar',    'label' => 'Receitas'],
    'despesas'    => ['href' => 'pages/despesas/listar.php',     'icon' => 'fa-file-invoice-dollar',    'label' => 'Despesas'],
    'fluxo_caixa' => ['href' => 'pages/fluxo_caixa/listar.php',  'icon' => 'fa-scale-balanced',         'label' => 'Fluxo de Caixa'],
    'categorias'  => ['href' => 'pages/categorias/listar.php',   'icon' => 'fa-tags',                   'label' => 'Gerenciar Categorias'],
];
?>
<aside class="sidebar">
    <div class="brand">
        <span class="brand-title">
            <img src="<?= $sidebarRoot ?>img/4union-logo_extenso.png" alt="4 Union" class="sidebar-logo sidebar-logo-full">
            <img src="<?= $sidebarRoot ?>img/4union-logo.png" alt="4 Union" class="sidebar-logo sidebar-logo-compact">
        </span>
        <button type="button" class="btn-toggle-sidebar" onclick="toggleSidebar()" title="Recolher/Expandir Menu">
            <i class="fas fa-chevron-left"></i>
        </button>
    </div>
    <nav>
        <?php foreach ($navItems as $key => $item): ?>
        <a href="<?= $sidebarRoot . $item['href'] ?>"<?= $sidebarActive === $key ? ' class="active"' : '' ?>><i class="fas <?= $item['icon'] ?>"></i> <span><?= $item['label'] ?></span></a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= $sidebarRoot ?>logout.php"><i class="fas fa-sign-out-alt"></i> <span>Sair</span></a>
    </div>
</aside>
