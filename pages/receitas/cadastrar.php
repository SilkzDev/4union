<?php
// pages/receitas/cadastrar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
}

require_once "../../config/conexao.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_id       = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;
    $descricao        = trim($_POST['descricao'] ?? '');
    $cliente_id       = $_POST['cliente_id'] ?? null;
    $categoria_id     = $_POST['categoria_id'] ?? null;
    $valor            = $_POST['valor'] ?? '0.00'; // Mantém em string para exatidão
    $data_vencimento  = $_POST['data_vencimento'] ?? '';
    $forma_pagamento  = $_POST['forma_pagamento'] ?? '';
    $conta_financeira = $_POST['conta_financeira'] ?? '';
    $status           = $_POST['status'] ?? 'Pendente';
    $observacoes      = trim($_POST['observacoes'] ?? '');

    try {
        $sql = "INSERT INTO receitas (usuario_id, cliente_id, categoria_id, descricao, valor, data_vencimento, forma_pagamento, conta_financeira, status, observacoes) 
                VALUES (:usuario_id, :cliente_id, :categoria_id, :descricao, :valor, :data_vencimento, :forma_pagamento, :conta_financeira, :status, :observacoes)";
        
        $stmt = $conexao->prepare($sql);
        $stmt->execute([
            'usuario_id'       => $usuario_id,
            'cliente_id'       => $cliente_id,
            'categoria_id'     => $categoria_id,
            'descricao'        => $descricao,
            'valor'            => $valor,
            'data_vencimento'  => $data_vencimento,
            'forma_pagamento'  => $forma_pagamento,
            'conta_financeira' => $conta_financeira,
            'status'           => $status,
            'observacoes'      => $observacoes
        ]);

        $_SESSION['sucesso'] = "Receita cadastrada com sucesso!";
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao cadastrar receita: " . $e->getMessage();
    }
}

header("Location: listar.php");
exit;