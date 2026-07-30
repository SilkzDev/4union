<?php
// pages/receitas/listar.php
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

// 1. Carregar APENAS Categorias do Tipo RECEITA
try {
    $sql_cat = "SELECT id, nome, tipo 
                FROM categorias 
                WHERE usuario_id = :usuario_id 
                  AND (ativo = 'S' OR ativo = '1' OR ativo IS NULL) 
                  AND (UPPER(tipo) = 'RECEITA' OR tipo IS NULL OR tipo = '')
                ORDER BY nome ASC";
    $stmt_cat = $conexao->prepare($sql_cat);
    $stmt_cat->execute(['usuario_id' => $usuario_id]);
    $categorias_receitas = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categorias_receitas = [];
}

// 2. Carregar Clientes
try {
    $sql_cli = "SELECT id, nome FROM clientes WHERE ativo = 'S' ORDER BY nome ASC";
    $stmt_cli = $conexao->query($sql_cli);
    $clientes = $stmt_cli->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $clientes = [];
}

// 3. Carregar Formas de Pagamento
try {
    $sql_fp = "SELECT id, descricao FROM formas_pagamento WHERE ativo = 'S' ORDER BY descricao ASC";
    $stmt_fp = $conexao->query($sql_fp);
    $formas_pagamento = $stmt_fp->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $formas_pagamento = [];
}

// 4. Listar Receitas
try {
    $sql = "SELECT r.*, c.nome AS categoria_nome, fp.descricao AS forma_pagamento_nome 
            FROM receitas r 
            LEFT JOIN categorias c ON r.categoria_id = c.id 
            LEFT JOIN formas_pagamento fp ON r.forma_pagamento_id = fp.id 
            WHERE r.usuario_id = :usuario_id 
            ORDER BY r.data_recebimento DESC";
    $stmt = $conexao->prepare($sql);
    $stmt->execute(['usuario_id' => $usuario_id]);
    $receitas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $receitas = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receitas - 4 Union</title>
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

        .modal-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 9999; display: flex; align-items: center; justify-content: center; padding: 20px; box-sizing: border-box; }
        .modal-container { background: #ffffff; border-radius: 12px; padding: 24px 28px; width: 100%; max-width: 650px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); animation: modalSlideIn 0.2s ease-out; }
        @keyframes modalSlideIn { from { opacity: 0; transform: translateY(-15px); } to { opacity: 1; transform: translateY(0); } }
        .modal-header-custom { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 20px; }
        .modal-header-custom h3 { margin: 0; font-size: 1.25rem; color: #1e293b; font-weight: 700; }
        .modal-close-btn { background: transparent; border: none; font-size: 1.25rem; color: #94a3b8; cursor: pointer; padding: 4px 8px; border-radius: 6px; transition: all 0.2s; }
        .modal-close-btn:hover { background: #f1f5f9; color: #0f172a; }
        .form-group-custom { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        .form-group-custom label { font-size: 0.875rem; font-weight: 600; color: #334155; }
        .form-group-custom input, .form-group-custom select, .form-group-custom textarea { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; outline: none; box-sizing: border-box; background-color: #ffffff; color: #0f172a; transition: border-color 0.2s, box-shadow 0.2s; }
        .form-group-custom input:focus, .form-group-custom select:focus, .form-group-custom textarea:focus { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.15); }
        .modal-footer-custom { display: flex; justify-content: flex-end; gap: 12px; margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
        .btn-modal-sec { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 10px 18px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: background 0.2s; }
        .btn-modal-sec:hover { background: #e2e8f0; }
        .btn-modal-pri { background: #16a34a; color: #ffffff; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: background 0.2s; text-decoration: none; }
        .btn-modal-pri:hover { background: #15803d; }

        .modal-container-excluir { background: #ffffff; border-radius: 16px; padding: 24px; width: 100%; max-width: 460px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); animation: modalSlideIn 0.2s ease-out; }
        .modal-header-excluir { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .modal-title-excluir { margin: 0; font-size: 1.25rem; color: #dc2626; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .modal-body-excluir { font-size: 0.95rem; color: #334155; margin-bottom: 24px; text-align: left; }
        .modal-body-excluir p { margin: 0 0 6px 0; line-height: 1.5; }
        .modal-body-excluir .subtext-excluir { color: #64748b; font-size: 0.875rem; margin: 0; }
        .modal-footer-excluir { display: flex; justify-content: flex-end; gap: 12px; }
        .btn-cancelar-excluir { background: #f8fafc; color: #475569; border: 1px solid #cbd5e1; padding: 9px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; transition: all 0.2s; }
        .btn-cancelar-excluir:hover { background: #f1f5f9; }
        .btn-confirmar-excluir { background: #dc2626; color: #ffffff; border: none; padding: 9px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.9rem; text-decoration: none; display: inline-block; transition: background 0.2s; }
        .btn-confirmar-excluir:hover { background: #b91c1c; }
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
            <a href="listar.php" class="active"><i class="fas fa-hand-holding-dollar"></i> <span>Receitas</span></a>
            <a href="../despesas/listar.php"><i class="fas fa-file-invoice-dollar"></i> <span>Despesas</span></a>
            <a href="../fluxo_caixa/listar.php"><i class="fas fa-scale-balanced"></i> <span>Fluxo de Caixa</span></a>
            <a href="../categorias/listar.php"><i class="fas fa-tags"></i> <span>Gerenciar Categorias</span></a>
            <a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Sair</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Gestão de Receitas</h1>
                <p>Bem-vindo(a), <strong><?= htmlspecialchars($usuario_nome) ?></strong></p>
            </div>
            <div class="topbar-actions">
                <button type="button" class="btn-add" onclick="abrirModalCadastrar()">
                    <i class="fas fa-plus"></i> Nova Receita
                </button>
            </div>
        </header>

        <?php if (isset($_SESSION['sucesso'])): ?>
            <div class="alert alert-success">✓ <?= $_SESSION['sucesso']; unset($_SESSION['sucesso']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['erro'])): ?>
            <div class="alert alert-error">⚠️ <?= $_SESSION['erro']; unset($_SESSION['erro']); ?></div>
        <?php endif; ?>

        <div class="card">
            <h3 style="margin-bottom:15px;">Receitas Cadastradas</h3>

            <?php if (empty($receitas)): ?>
                <div style="text-align: center; padding: 40px 20px; color: #64748b;">
                    <i class="fas fa-hand-holding-dollar" style="font-size: 48px; color: #cbd5e1; margin-bottom: 15px; display: block;"></i>
                    <h4 style="font-size: 18px; color: #334155; margin-bottom: 8px;">Nenhuma receita registrada</h4>
                    <p style="font-size: 14px; margin-bottom: 20px;">Comece adicionando suas fontes de renda.</p>
                    <button type="button" class="btn-add" onclick="abrirModalCadastrar()" style="display: inline-flex; align-items: center; gap: 8px;">
                        <i class="fas fa-plus"></i> Cadastrar Nova Receita
                    </button>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Data</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receitas as $row): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['descricao']) ?></strong></td>
                                <td><?= htmlspecialchars($row['categoria_nome'] ?? 'Sem Categoria') ?></td>
                                <td style="color:#16a34a; font-weight:600;">
                                    R$ <?= formatarValorSemArredondar($row['valor']) ?>
                                </td>
                                <td><?= !empty($row['data_recebimento']) ? date('d/m/Y', strtotime($row['data_recebimento'])) : '-' ?></td>
                                <td>
                                    <span class="badge <?= ($row['status'] === 'Recebido' || $row['status'] === 'Pago') ? 'badge-receita' : 'badge-despesa' ?>">
                                        <?= htmlspecialchars($row['status']) ?>
                                    </span>
                                </td>
                                <td class="action-links">
                                    <a href="javascript:void(0)" style="color:#64748b;" onclick="abrirModalVisualizar(<?= $row['id'] ?>)">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                    <a href="javascript:void(0)" style="color:#2563eb;" onclick="abrirModalEditar(<?= $row['id'] ?>)">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <a href="javascript:void(0)" style="color:#dc2626;" onclick="abrirModalExcluir(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['descricao']), ENT_QUOTES) ?>')">
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

    <!-- MODAL CADASTRAR -->
    <div id="modalCadastrar" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-header-custom">
                <h3>Cadastrar Receita</h3>
                <button type="button" class="modal-close-btn" onclick="fecharModal('modalCadastrar')"><i class="fas fa-times"></i></button>
            </div>

            <form id="formCadastrar" action="salvar.php" method="POST">
                <input type="hidden" name="acao" value="cadastrar">

                <div class="form-group-custom">
                    <label for="cad_descricao">Descrição *</label>
                    <input type="text" id="cad_descricao" name="descricao" required placeholder="Ex: Venda de Serviços">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group-custom">
                        <label for="cad_cliente_id">Cliente *</label>
                        <select id="cad_cliente_id" name="cliente_id" required>
                            <option value="" disabled selected>Selecione...</option>
                            <option value="0">Cliente Padrão</option>
                            <?php foreach ($clientes as $cli): ?>
                                <option value="<?= $cli['id'] ?>"><?= htmlspecialchars($cli['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label for="cad_categoria_id">Categoria *</label>
                        <select id="cad_categoria_id" name="categoria_id" required>
                            <option value="" disabled selected>Selecione...</option>
                            <?php if (empty($categorias_receitas)): ?>
                                <option value="" disabled>Nenhuma categoria de receita cadastrada</option>
                            <?php else: ?>
                                <?php foreach ($categorias_receitas as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group-custom">
                        <label for="cad_valor">Valor Monetário (R$) *</label>
                        <input type="text" id="cad_valor" name="valor" placeholder="0,00" required class="input-mascara-moeda">
                    </div>

                    <div class="form-group-custom">
                        <label for="cad_data_recebimento">Data *</label>
                        <input type="date" id="cad_data_recebimento" name="data_recebimento" max="9999-12-31" required class="input-limita-data">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group-custom">
                        <label for="cad_forma_pagamento_id">Forma de Pagamento *</label>
                        <select id="cad_forma_pagamento_id" name="forma_pagamento_id" required>
                            <option value="" disabled selected>Selecione...</option>
                            <?php foreach ($formas_pagamento as $fp): ?>
                                <option value="<?= $fp['id'] ?>"><?= htmlspecialchars($fp['descricao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label for="cad_conta_id">Conta Financeira *</label>
                        <select id="cad_conta_id" name="conta_id" required>
                            <option value="1">Conta Principal</option>
                            <option value="2">Caixa Físico</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-custom">
                    <label for="cad_status">Status *</label>
                    <select id="cad_status" name="status" required>
                        <option value="Pendente">Pendente</option>
                        <option value="Recebido">Recebido / Pago</option>
                    </select>
                </div>

                <div class="form-group-custom">
                    <label for="cad_observacoes">Observações</label>
                    <textarea id="cad_observacoes" name="observacoes" rows="3" placeholder="Informações adicionais..."></textarea>
                </div>

                <div class="modal-footer-custom">
                    <button type="button" class="btn-modal-sec" onclick="fecharModal('modalCadastrar')">Cancelar</button>
                    <button type="submit" class="btn-modal-pri">Salvar Receita</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDITAR -->
    <div id="modalEditar" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-header-custom">
                <h3>Editar Receita</h3>
                <button type="button" class="modal-close-btn" onclick="fecharModal('modalEditar')"><i class="fas fa-times"></i></button>
            </div>

            <form id="formEditar" action="salvar.php" method="POST">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" id="edit_id" name="id">

                <div class="form-group-custom">
                    <label for="edit_descricao">Descrição *</label>
                    <input type="text" id="edit_descricao" name="descricao" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group-custom">
                        <label for="edit_cliente_id">Cliente *</label>
                        <select id="edit_cliente_id" name="cliente_id" required>
                            <option value="" disabled>Selecione...</option>
                            <option value="0">Cliente Padrão</option>
                            <?php foreach ($clientes as $cli): ?>
                                <option value="<?= $cli['id'] ?>"><?= htmlspecialchars($cli['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label for="edit_categoria_id">Categoria *</label>
                        <select id="edit_categoria_id" name="categoria_id" required>
                            <option value="" disabled>Selecione...</option>
                            <?php if (empty($categorias_receitas)): ?>
                                <option value="" disabled>Nenhuma categoria de receita cadastrada</option>
                            <?php else: ?>
                                <?php foreach ($categorias_receitas as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['nome']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group-custom">
                        <label for="edit_valor">Valor Monetário (R$) *</label>
                        <input type="text" id="edit_valor" name="valor" placeholder="0,00" required class="input-mascara-moeda">
                    </div>

                    <div class="form-group-custom">
                        <label for="edit_data_recebimento">Data *</label>
                        <input type="date" id="edit_data_recebimento" name="data_recebimento" max="9999-12-31" required class="input-limita-data">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group-custom">
                        <label for="edit_forma_pagamento_id">Forma de Pagamento *</label>
                        <select id="edit_forma_pagamento_id" name="forma_pagamento_id" required>
                            <option value="" disabled>Selecione...</option>
                            <?php foreach ($formas_pagamento as $fp): ?>
                                <option value="<?= $fp['id'] ?>"><?= htmlspecialchars($fp['descricao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group-custom">
                        <label for="edit_conta_id">Conta Financeira *</label>
                        <select id="edit_conta_id" name="conta_id" required>
                            <option value="1">Conta Principal</option>
                            <option value="2">Caixa Físico</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-custom">
                    <label for="edit_status">Status *</label>
                    <select id="edit_status" name="status" required>
                        <option value="Pendente">Pendente</option>
                        <option value="Recebido">Recebido / Pago</option>
                    </select>
                </div>

                <div class="form-group-custom">
                    <label for="edit_observacoes">Observações</label>
                    <textarea id="edit_observacoes" name="observacoes" rows="3"></textarea>
                </div>

                <div class="modal-footer-custom">
                    <button type="button" class="btn-modal-sec" onclick="fecharModal('modalEditar')">Cancelar</button>
                    <button type="submit" class="btn-modal-pri">Atualizar Receita</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL VISUALIZAR -->
    <div id="modalVisualizar" class="modal-overlay" style="display: none;">
        <div class="modal-container" style="max-width: 500px;">
            <div class="modal-header-custom">
                <h3>Detalhes da Receita</h3>
                <button type="button" class="modal-close-btn" onclick="fecharModal('modalVisualizar')"><i class="fas fa-times"></i></button>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:12px; font-size: 0.95rem;">
                <div><strong>Descrição:</strong> <span id="ver_descricao"></span></div>
                <div><strong>Cliente:</strong> <span id="ver_cliente"></span></div>
                <div><strong>Categoria:</strong> <span id="ver_categoria"></span></div>
                <div><strong>Valor:</strong> <span id="ver_valor" style="color:#16a34a; font-weight:bold;"></span></div>
                <div><strong>Data:</strong> <span id="ver_data"></span></div>
                <div><strong>Forma de Pagamento:</strong> <span id="ver_forma_pagamento"></span></div>
                <div><strong>Conta Financeira:</strong> <span id="ver_conta_financeira"></span></div>
                <div><strong>Status:</strong> <span id="ver_status"></span></div>
                <div><strong>Observações:</strong> <span id="ver_observacoes"></span></div>
            </div>

            <div class="modal-footer-custom" style="margin-top:20px;">
                <button type="button" class="btn-modal-sec" onclick="fecharModal('modalVisualizar')">Fechar</button>
            </div>
        </div>
    </div>

    <!-- MODAL EXCLUIR -->
    <div id="modalExcluir" class="modal-overlay" style="display: none;">
        <div class="modal-container-excluir">
            <div class="modal-header-excluir">
                <h3 class="modal-title-excluir">
                    <i class="fas fa-exclamation-triangle"></i> Excluir Receita
                </h3>
                <button type="button" class="modal-close-btn" onclick="fecharModal('modalExcluir')">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="modal-body-excluir">
                <p>Tem certeza que deseja excluir a receita <strong id="excluir_nome_item"></strong>?</p>
                <p class="subtext-excluir">Esta ação removerá o registro e não poderá ser desfeita.</p>
            </div>

            <div class="modal-footer-excluir">
                <button type="button" class="btn-cancelar-excluir" onclick="fecharModal('modalExcluir')">Cancelar</button>
                <a id="btnConfirmarExcluir" href="#" class="btn-confirmar-excluir">Sim, Excluir</a>
            </div>
        </div>
    </div>

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

        function abrirModalCadastrar() {
            document.getElementById('formCadastrar').reset();
            document.getElementById('modalCadastrar').style.display = 'flex';
        }

        function fecharModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function abrirModalExcluir(id, descricao) {
            document.getElementById('btnConfirmarExcluir').href = 'excluir.php?id=' + id;
            document.getElementById('excluir_nome_item').innerText = descricao || 'esta receita';
            document.getElementById('modalExcluir').style.display = 'flex';
        }

        function aplicarMascaraMoeda(input) {
            let v = input.value.replace(/\D/g, '');
            if (!v) {
                input.value = '';
                return;
            }
            
            while (v.length < 3) {
                v = '0' + v;
            }
            
            let inteiros = v.slice(0, -2);
            let centavos = v.slice(-2);
            
            inteiros = inteiros.replace(/^0+(?=\d)/, '');
            inteiros = inteiros.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            
            input.value = inteiros + ',' + centavos;
        }

        function formatarNumeroParaMoedaBr(valor) {
            if (valor === null || valor === undefined || valor === '') return '0,00';
            
            let str = String(valor).trim();
            let partes = str.split('.');
            let inteiros = partes[0] || '0';
            let centavos = partes[1] || '00';
            
            if (centavos.length < 2) {
                centavos = centavos.padEnd(2, '0');
            } else if (centavos.length > 2) {
                centavos = centavos.substring(0, 2);
            }
            
            inteiros = inteiros.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            return inteiros + ',' + centavos;
        }

        document.querySelectorAll('.input-mascara-moeda').forEach(input => {
            input.addEventListener('input', function() {
                aplicarMascaraMoeda(this);
            });
        });

        document.querySelectorAll('.input-limita-data').forEach(input => {
            input.addEventListener('input', function() {
                if (this.value) {
                    const partes = this.value.split('-');
                    if (partes[0] && partes[0].length > 4) {
                        partes[0] = partes[0].substring(0, 4);
                        this.value = partes.join('-');
                    }
                }
            });
        });

        function abrirModalEditar(id) {
            fetch('editar.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        const rec = data.dados;
                        document.getElementById('edit_id').value = rec.id;
                        document.getElementById('edit_descricao').value = rec.descricao;
                        
                        let cliId = (rec.cliente_id !== null && rec.cliente_id !== '' && rec.cliente_id !== undefined) ? rec.cliente_id : '0';
                        document.getElementById('edit_cliente_id').value = cliId;

                        document.getElementById('edit_categoria_id').value = rec.categoria_id;
                        document.getElementById('edit_valor').value = formatarNumeroParaMoedaBr(rec.valor);
                        document.getElementById('edit_data_recebimento').value = rec.data_recebimento;
                        document.getElementById('edit_forma_pagamento_id').value = rec.forma_pagamento_id;
                        document.getElementById('edit_conta_id').value = rec.conta_id || 1;
                        document.getElementById('edit_status').value = rec.status;
                        document.getElementById('edit_observacoes').value = rec.observacao || rec.observacoes || '';
                        document.getElementById('modalEditar').style.display = 'flex';
                    } else {
                        alert(data.mensagem || 'Erro ao carregar dados.');
                    }
                })
                .catch(() => alert('Erro na comunicação com o servidor.'));
        }

        function abrirModalVisualizar(id) {
            fetch('visualizar.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.sucesso) {
                        const rec = data.dados;
                        document.getElementById('ver_descricao').innerText = rec.descricao;
                        document.getElementById('ver_cliente').innerText = rec.cliente_nome || 'Cliente Padrão';
                        document.getElementById('ver_categoria').innerText = rec.categoria_nome || 'Sem Categoria';
                        document.getElementById('ver_valor').innerText = 'R$ ' + formatarNumeroParaMoedaBr(rec.valor);
                        document.getElementById('ver_data').innerText = rec.data_formatada || rec.data_recebimento;
                        document.getElementById('ver_forma_pagamento').innerText = rec.forma_pagamento_nome || '-';
                        document.getElementById('ver_conta_financeira').innerText = rec.conta_nome || 'Conta Principal';
                        document.getElementById('ver_status').innerText = rec.status;
                        document.getElementById('ver_observacoes').innerText = rec.observacao || rec.observacoes || 'Nenhuma';
                        document.getElementById('modalVisualizar').style.display = 'flex';
                    } else {
                        alert(data.mensagem || 'Erro ao carregar detalhes.');
                    }
                })
                .catch(() => alert('Erro na comunicação com o servidor.'));
        }

        window.onclick = function(event) {
            if (event.target.classList.contains('modal-overlay')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>