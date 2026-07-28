<?php
// pages/categorias/listar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
}

require_once "../../config/conexao.php";

// Captura flexível do ID do usuário logado
$usuario_id   = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;
$usuario_nome = $_SESSION['usuario_nome'] ?? $_SESSION['nome'] ?? 'Usuário';

// Filtros GET
$filtro_nome   = isset($_GET['f_nome']) ? trim($_GET['f_nome']) : '';
$filtro_tipo   = isset($_GET['f_tipo']) ? trim($_GET['f_tipo']) : '';
$filtro_status = isset($_GET['f_status']) ? trim($_GET['f_status']) : '';

$tem_filtro_ativo = (!empty($filtro_nome) || !empty($filtro_tipo) || !empty($filtro_status));

try {
    $sql = "SELECT * FROM categorias WHERE usuario_id = :usuario_id";
    $params = ['usuario_id' => $usuario_id];

    if (!empty($filtro_nome)) {
        $sql .= " AND nome LIKE :nome";
        $params['nome'] = "%" . $filtro_nome . "%";
    }
    if (!empty($filtro_tipo)) {
        $sql .= " AND tipo = :tipo";
        $params['tipo'] = $filtro_tipo;
    }
    if (!empty($filtro_status)) {
        $sql .= " AND ativo = :status";
        $params['status'] = $filtro_status;
    }

    $sql .= " ORDER BY tipo ASC, nome ASC";
    $stmt = $conexao->prepare($sql);
    $stmt->execute($params);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorias - 4 Union</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Arquivo CSS isolado -->
    <link rel="stylesheet" href="../../assets/css/listar.css">

    <!-- Script Inline para restaurar o estado da Sidebar antes de carregar a página (Evita piscar a tela) -->
    <script>
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed-init');
        }
    </script>

    <!-- Estilização das Modais e Toggle da Sidebar -->
    <style>
        /* Ajustes no Brand da Sidebar para conter o Botão */
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

        /* Estilo do Botão de Toggle DENTRO da Sidebar */
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

        /* Suavização de Transição */
        .sidebar, .main-content {
            transition: all 0.3s ease-in-out !important;
        }

        /* Estado Recolhido da Sidebar */
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

        /* Oculta o título da marca e exibe apenas o botão centralizado quando recolhido */
        html.sidebar-collapsed-init .sidebar .brand,
        body.sidebar-collapsed .sidebar .brand {
            justify-content: center !important;
        }

        html.sidebar-collapsed-init .sidebar .brand-title,
        body.sidebar-collapsed .sidebar .brand-title {
            display: none !important;
        }

        /* Gira a seta indicando expansão ao clicar */
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

        /* Modais */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(3px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease-in-out;
        }

        .modal-container {
            background: #ffffff;
            width: 100%;
            max-width: 440px;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            padding: 28px;
            position: relative;
            border: 1px solid #e2e8f0;
        }

        .modal-header-custom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .modal-header-custom h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f172a;
        }

        .modal-close-btn {
            background: transparent;
            border: none;
            font-size: 18px;
            color: #94a3b8;
            cursor: pointer;
        }

        .modal-close-btn:hover {
            color: #ef4444;
        }

        .form-group-custom {
            margin-bottom: 18px;
        }

        .form-group-custom label {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 7px;
        }

        .form-group-custom input[type="text"],
        .form-group-custom select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.92rem;
            color: #1e293b;
            background-color: #ffffff;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group-custom input[type="text"]:focus,
        .form-group-custom select:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .color-picker-box {
            background-color: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 6px;
            display: flex;
            align-items: center;
            height: 44px;
            box-sizing: border-box;
        }

        .color-picker-box input[type="color"] {
            -webkit-appearance: none;
            border: none;
            width: 100%;
            height: 100%;
            border-radius: 4px;
            cursor: pointer;
            background: transparent;
        }

        .color-picker-box input[type="color"]::-webkit-color-swatch-wrapper {
            padding: 0;
        }

        .color-picker-box input[type="color"]::-webkit-color-swatch {
            border: none;
            border-radius: 4px;
        }

        .modal-footer-custom {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 24px;
        }

        .btn-modal-sec {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 9px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .btn-modal-pri {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 9px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .btn-modal-pri:hover {
            background: #1d4ed8;
        }

        .btn-modal-danger {
            background: #dc2626;
            color: #ffffff;
            border: none;
            padding: 9px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }

        .btn-modal-danger:hover {
            background: #b91c1c;
        }

        .view-detail-card {
            display: flex;
            align-items: center;
            gap: 16px;
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 16px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.97); }
            to { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="brand">
            <span class="brand-title"><i class="fas fa-chart-line"></i> <span>4 Union</span></span>
            
            <!-- BOTÃO LOCALIZADO NA SIDEBAR -->
            <button type="button" class="btn-toggle-sidebar" onclick="toggleSidebar()" title="Recolher/Expandir Menu">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        <nav>
            <a href="../../dashboard.php"><i class="fas fa-home"></i> <span>Visão Geral</span></a>
            <a href="../receitas/index.php"><i class="fas fa-hand-holding-dollar"></i> <span>Receitas</span></a>
            <a href="../despesas/listar.php"><i class="fas fa-file-invoice-dollar"></i> <span>Despesas</span></a>
            <a href="listar.php" class="active"><i class="fas fa-tags"></i> <span>Gerenciar Categorias</span></a>
            <a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Sair</span></a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="topbar">
            <div>
                <h1 style="margin: 0; font-size: 1.5rem;">Gerenciar Categorias</h1>
                <p style="margin: 0; color: #64748b; font-size: 0.9rem;">Bem-vindo(a), <strong><?= htmlspecialchars($usuario_nome) ?></strong></p>
            </div>

            <div class="topbar-actions">
                <button type="button" class="btn-add" onclick="abrirModalCadastrar()">
                    <i class="fas fa-plus"></i> Nova Categoria
                </button>
                
                <button type="button" id="btnToggleFiltros" class="btn-search" onclick="toggleFiltros()">
                    <i class="fas fa-search"></i> Procurar Categorias
                </button>
            </div>
        </header>

        <?php if (isset($_SESSION['sucesso'])): ?>
            <div class="alert alert-success">✓ <?= $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['erro'])): ?>
            <div class="alert alert-error">⚠️ <?= $_SESSION['erro']; unset($_SESSION['erro']); ?></div>
        <?php endif; ?>

        <!-- ÁREA DE FILTROS -->
        <div id="painelFiltros" class="filter-section" style="<?= $tem_filtro_ativo ? 'display:block;' : 'display:none;'; ?>">
            <h4 style="margin-bottom:12px;"><i class="fas fa-filter"></i> Filtrar Busca</h4>
            <form action="listar.php" method="GET">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Nome</label>
                        <input type="text" name="f_nome" value="<?= htmlspecialchars($filtro_nome) ?>">
                    </div>
                    <div class="filter-group">
                        <label>Tipo</label>
                        <select name="f_tipo">
                            <option value="">Todos</option>
                            <option value="Receita" <?= $filtro_tipo === 'Receita' ? 'selected' : '' ?>>Receita</option>
                            <option value="Despesa" <?= $filtro_tipo === 'Despesa' ? 'selected' : '' ?>>Despesa</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="f_status">
                            <option value="">Todos</option>
                            <option value="S" <?= $filtro_status === 'S' ? 'selected' : '' ?>>Ativa</option>
                            <option value="N" <?= $filtro_status === 'N' ? 'selected' : '' ?>>Inativa</option>
                        </select>
                    </div>
                </div>
                <div class="filter-buttons">
                    <a href="listar.php" class="btn-clear">Limpar</a>
                    <button type="submit" class="btn-apply"><i class="fas fa-search"></i> Filtrar</button>
                </div>
            </form>
        </div>

        <!-- TABELA / DADOS -->
        <div class="card">
            <h3 style="margin-bottom:15px;">Categorias Cadastradas</h3>

            <?php if (empty($categorias)): ?>
                <div style="text-align: center; padding: 40px 20px; color: #64748b;">
                    <i class="fas fa-tags" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px; display: block;"></i>
                    <h4 style="font-size: 18px; color: #334155; margin-bottom: 8px;">Nenhuma categoria registrada</h4>
                    <p style="font-size: 14px; margin-bottom: 20px;">Você ainda não possui categorias para seu perfil.</p>
                    <button type="button" class="btn-add" onclick="abrirModalCadastrar()" style="display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus"></i> Ingressar Nova Categoria
                    </button>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categorias as $row): ?>
                            <?php 
                                $cor_categoria   = !empty($row['cor']) ? $row['cor'] : '#1e3c72';
                                $icone_categoria = !empty($row['icone']) ? $row['icone'] : 'fa-tag';
                            ?>
                            <tr>
                                <td>
                                    <div class="category-item-info">
                                        <span class="category-icon-box" style="background-color: <?= htmlspecialchars($cor_categoria) ?>;">
                                            <i class="fas <?= htmlspecialchars($icone_categoria) ?>"></i>
                                        </span>
                                        <strong><?= htmlspecialchars($row['nome']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $row['tipo'] === 'Receita' ? 'badge-receita' : 'badge-despesa' ?>">
                                        <?= htmlspecialchars($row['tipo']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $row['ativo'] === 'S' 
                                        ? '<span style="color:#16a34a; font-weight:600;">Ativa</span>' 
                                        : '<span style="color:#dc2626; font-weight:600;">Inativa</span>' 
                                    ?>
                                </td>
                                <td class="action-links">
                                    <a href="javascript:void(0)" style="color:#64748b;" onclick="abrirModalVer(
                                        '<?= htmlspecialchars($row['nome'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['tipo'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($cor_categoria, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($icone_categoria, ENT_QUOTES) ?>',
                                        '<?= $row['ativo'] ?>'
                                    )">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>

                                    <a href="javascript:void(0)" style="color:#2563eb;" onclick="abrirModalEditar(
                                        '<?= $row['id'] ?>',
                                        '<?= htmlspecialchars($row['nome'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($row['tipo'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($cor_categoria, ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($icone_categoria, ENT_QUOTES) ?>',
                                        '<?= $row['ativo'] ?>'
                                    )">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>

                                    <a href="javascript:void(0)" class="btn-acao-excluir" style="color:#dc2626;" onclick="abrirModalExcluir('<?= $row['id'] ?>', '<?= htmlspecialchars($row['nome'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-trash"></i> Excluir
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <!-- MODAL 1: ADICIONAR NOVA CATEGORIA -->
    <div id="modalCadastrar" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header-custom">
                <h3>Nova Categoria</h3>
                <button type="button" class="modal-close-btn" onclick="fecharModal('modalCadastrar')"><i class="fas fa-times"></i></button>
            </div>

            <form action="salvar.php" method="POST">
                <input type="hidden" name="acao" value="cadastrar">

                <div class="form-group-custom">
                    <label for="cadNome">Nome da Categoria *</label>
                    <input type="text" id="cadNome" name="nome" required placeholder="Digite o nome da categoria">
                </div>

                <div class="form-group-custom">
                    <label for="cadTipo">Tipo Financeiro *</label>
                    <select id="cadTipo" name="tipo" required>
                        <option value="">-- Selecione --</option>
                        <option value="Receita">Receita</option>
                        <option value="Despesa">Despesa</option>
                    </select>
                </div>

                <div class="form-group-custom">
                    <label for="cadCor">Cor Identificadora</label>
                    <div class="color-picker-box">
                        <input type="color" id="cadCor" name="cor" value="#2b4c7e">
                    </div>
                </div>

                <div class="form-group-custom">
                    <label for="cadIcone">Ícone (FontAwesome)</label>
                    <select id="cadIcone" name="icone">
                        <option value="fa-tag">Etiqueta Padrão</option>
                        <option value="fa-utensils">Alimentação</option>
                        <option value="fa-car">Transporte</option>
                        <option value="fa-wallet">Salário / Renda</option>
                        <option value="fa-home">Moradia / Casa</option>
                        <option value="fa-shopping-cart">Compras / Mercado</option>
                        <option value="fa-heartbeat">Saúde / Medicamentos</option>
                        <option value="fa-graduation-cap">Educação</option>
                        <option value="fa-film">Lazer / Entretenimento</option>
                        <option value="fa-bolt">Contas / Energia</option>
                        <option value="fa-wifi">Internet / Telefone</option>
                    </select>
                </div>

                <div class="form-group-custom">
                    <label for="cadAtivo">Status *</label>
                    <select id="cadAtivo" name="ativo" required>
                        <option value="S">Ativa</option>
                        <option value="N">Inativa</option>
                    </select>
                </div>

                <div class="modal-footer-custom">
                    <button type="button" class="btn-modal-sec" onclick="fecharModal('modalCadastrar')">Cancelar</button>
                    <button type="submit" class="btn-modal-pri">Salvar Categoria</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDITAR CATEGORIA -->
    <div id="modalEditar" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header-custom">
                <h3>Editar Categoria</h3>
                <button type="button" class="modal-close-btn" onclick="fecharModal('modalEditar')"><i class="fas fa-times"></i></button>
            </div>

            <form action="salvar.php" method="POST">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" id="editId" name="id_categoria">

                <div class="form-group-custom">
                    <label for="editNome">Nome da Categoria *</label>
                    <input type="text" id="editNome" name="nome" required placeholder="Digite o nome da categoria">
                </div>

                <div class="form-group-custom">
                    <label for="editTipo">Tipo Financeiro *</label>
                    <select id="editTipo" name="tipo" required>
                        <option value="">-- Selecione --</option>
                        <option value="Receita">Receita</option>
                        <option value="Despesa">Despesa</option>
                    </select>
                </div>

                <div class="form-group-custom">
                    <label for="editCor">Cor Identificadora</label>
                    <div class="color-picker-box">
                        <input type="color" id="editCor" name="cor" value="#2b4c7e">
                    </div>
                </div>

                <div class="form-group-custom">
                    <label for="editIcone">Ícone (FontAwesome)</label>
                    <select id="editIcone" name="icone">
                        <option value="fa-tag">Etiqueta Padrão</option>
                        <option value="fa-utensils">Alimentação</option>
                        <option value="fa-car">Transporte</option>
                        <option value="fa-wallet">Salário / Renda</option>
                        <option value="fa-home">Moradia / Casa</option>
                        <option value="fa-shopping-cart">Compras / Mercado</option>
                        <option value="fa-heartbeat">Saúde / Medicamentos</option>
                        <option value="fa-graduation-cap">Educação</option>
                        <option value="fa-film">Lazer / Entretenimento</option>
                        <option value="fa-bolt">Contas / Energia</option>
                        <option value="fa-wifi">Internet / Telefone</option>
                    </select>
                </div>

                <div class="form-group-custom">
                    <label for="editAtivo">Status *</label>
                    <select id="editAtivo" name="ativo" required>
                        <option value="S">Ativa</option>
                        <option value="N">Inativa</option>
                    </select>
                </div>

                <div class="modal-footer-custom">
                    <button type="button" class="btn-modal-sec" onclick="fecharModal('modalEditar')">Cancelar</button>
                    <button type="submit" class="btn-modal-pri">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: VER DETALHES -->
    <div id="modalVer" class="modal-overlay">
        <div class="modal-container">
            <div class="modal-header-custom">
                <h3>Detalhes da Categoria</h3>
                <button type="button" class="modal-close-btn" onclick="fecharModal('modalVer')"><i class="fas fa-times"></i></button>
            </div>
            
            <div class="view-detail-card">
                <span id="verIconBox" class="category-icon-box" style="width:48px; height:48px; font-size:20px;"></span>
                <div>
                    <h4 id="verNome" style="margin:0; font-size:1.1rem; color:#1e293b;"></h4>
                    <span id="verTipo" class="badge" style="margin-top:4px; display:inline-block;"></span>
                </div>
            </div>

            <div class="form-group-custom">
                <label>Status:</label>
                <div id="verStatus" style="font-weight:600; font-size:0.95rem;"></div>
            </div>

            <div class="modal-footer-custom">
                <button type="button" class="btn-modal-sec" onclick="fecharModal('modalVer')">Fechar</button>
            </div>
        </div>
    </div>

    <!-- MODAL 4: CONFIRMAR EXCLUSÃO -->
    <div id="modalExcluir" class="modal-overlay">
        <div class="modal-container" style="max-width: 420px;">
            <div class="modal-header-custom">
                <h3 style="color: #dc2626; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-exclamation-triangle"></i> Excluir Categoria
                </h3>
                <button type="button" class="modal-close-btn" onclick="fecharModal('modalExcluir')"><i class="fas fa-times"></i></button>
            </div>

            <div style="margin-bottom: 20px; font-size: 0.95rem; color: #334155; line-height: 1.5;">
                Tem certeza que deseja excluir a categoria <strong id="excluirNome" style="color: #0f172a;"></strong>?
                <p style="font-size: 0.85rem; color: #64748b; margin-top: 8px; margin-bottom: 0;">
                    Esta ação removerá o registro e não poderá ser desfeita.
                </p>
            </div>

            <div class="modal-footer-custom">
                <button type="button" class="btn-modal-sec" onclick="fecharModal('modalExcluir')">Cancelar</button>
                <a id="btnConfirmarExclusao" href="#" class="btn-modal-danger">Sim, Excluir</a>
            </div>
        </div>
    </div>

    <script>
        // Função para alternar (ocultar/mostrar) a Sidebar
        function toggleSidebar() {
            document.documentElement.classList.remove('sidebar-collapsed-init');
            document.body.classList.toggle('sidebar-collapsed');
            
            const estaRecolhida = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', estaRecolhida ? 'true' : 'false');
        }

        // Aplica classe no body se já estiver salvo
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

        function abrirModalCadastrar() {
            document.getElementById('cadNome').value = '';
            document.getElementById('cadTipo').value = '';
            document.getElementById('cadCor').value = '#2b4c7e';
            document.getElementById('cadIcone').value = 'fa-tag';
            document.getElementById('cadAtivo').value = 'S';
            document.getElementById('modalCadastrar').style.display = 'flex';
        }

        function abrirModalVer(nome, tipo, cor, icone, ativo) {
            document.getElementById('verNome').innerText = nome;
            
            var badgeTipo = document.getElementById('verTipo');
            badgeTipo.innerText = tipo;
            badgeTipo.className = 'badge ' + (tipo === 'Receita' ? 'badge-receita' : 'badge-despesa');

            var box = document.getElementById('verIconBox');
            box.style.backgroundColor = cor;
            box.innerHTML = '<i class="fas ' + icone + '"></i>';

            document.getElementById('verStatus').innerHTML = (ativo === 'S') 
                ? '<span style="color:#16a34a;">● Ativa</span>' 
                : '<span style="color:#dc2626;">● Inativa</span>';

            document.getElementById('modalVer').style.display = 'flex';
        }

        function abrirModalEditar(id, nome, tipo, cor, icone, ativo) {
            document.getElementById('editId').value = id;
            document.getElementById('editNome').value = nome;
            document.getElementById('editTipo').value = tipo;
            document.getElementById('editCor').value = cor;
            document.getElementById('editAtivo').value = ativo;

            var selectIcone = document.getElementById('editIcone');
            if (selectIcone) {
                selectIcone.value = icone;
                if (!selectIcone.value) {
                    selectIcone.value = 'fa-tag';
                }
            }

            document.getElementById('modalEditar').style.display = 'flex';
        }

        function abrirModalExcluir(id, nome) {
            document.getElementById('excluirNome').innerText = nome;
            document.getElementById('btnConfirmarExclusao').href = 'excluir.php?id=' + id;
            document.getElementById('modalExcluir').style.display = 'flex';
        }

        function fecharModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('nova') === '1') {
                abrirModalCadastrar();
            }
        });

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }
    </script>

    <script src="../../assets/js/categorias.js?v=<?= time() ?>"></script>
</body>
</html>