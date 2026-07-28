<?php
// pages/despesas/salvar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
}

require_once "../../config/conexao.php";

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;
$acao       = $_POST['acao'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao          = trim($_POST['descricao'] ?? '');
    $fornecedor_id      = $_POST['fornecedor_id'] ?? '';
    $categoria_id       = $_POST['categoria_id'] ?? '';
    $valor_raw          = $_POST['valor'] ?? '0';
    $data_pagamento     = $_POST['data_pagamento'] ?? '';
    $forma_pagamento_id = $_POST['forma_pagamento_id'] ?? '';
    $conta_id           = $_POST['conta_id'] ?? '';
    $status             = $_POST['status'] ?? 'Pendente';
    $observacao         = trim($_POST['observacao'] ?? '');

    // Validação dos campos obrigatórios (matriz de testes da Sprint 07)
    if (empty($fornecedor_id)) {
        $_SESSION['erro'] = "Selecione um fornecedor.";
        header("Location: listar.php");
        exit;
    }

    if (empty($categoria_id)) {
        $_SESSION['erro'] = "Selecione uma categoria.";
        header("Location: listar.php");
        exit;
    }

    if (empty($descricao) || empty($data_pagamento) || empty($forma_pagamento_id) || empty($conta_id)) {
        $_SESSION['erro'] = "Preencha todos os campos obrigatórios (*).";
        header("Location: listar.php");
        exit;
    }

    // Tratamento de moeda no formato brasileiro (1.234,56 -> 1234.56)
    $valor_str = trim((string)$valor_raw);
    if (strpos($valor_str, ',') !== false) {
        $valor_str = str_replace('.', '', $valor_str);
        $valor_str = str_replace(',', '.', $valor_str);
    }
    $valor = (float)$valor_str;

    if ($valor <= 0) {
        $_SESSION['erro'] = "O valor da despesa deve ser maior que zero.";
        header("Location: listar.php");
        exit;
    }

    try {
        if ($acao === 'cadastrar') {
            $sql = "INSERT INTO despesas (usuario_id, fornecedor_id, categoria_id, forma_pagamento_id, conta_id, descricao, valor, data_pagamento, status, observacao, ativo)
                    VALUES (:usuario_id, :fornecedor_id, :categoria_id, :forma_pagamento_id, :conta_id, :descricao, :valor, :data_pagamento, :status, :observacao, 'S')";

            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                'usuario_id'         => $usuario_id,
                'fornecedor_id'      => $fornecedor_id,
                'categoria_id'       => $categoria_id,
                'forma_pagamento_id' => $forma_pagamento_id,
                'conta_id'           => $conta_id,
                'descricao'          => $descricao,
                'valor'              => $valor,
                'data_pagamento'     => $data_pagamento,
                'status'             => $status,
                'observacao'         => $observacao
            ]);

            $_SESSION['sucesso'] = "Despesa cadastrada com sucesso!";
        } elseif ($acao === 'editar') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                $_SESSION['erro'] = "ID de despesa inválido para edição.";
                header("Location: listar.php");
                exit;
            }

            $sql = "UPDATE despesas SET
                    fornecedor_id = :fornecedor_id,
                    categoria_id = :categoria_id,
                    forma_pagamento_id = :forma_pagamento_id,
                    conta_id = :conta_id,
                    descricao = :descricao,
                    valor = :valor,
                    data_pagamento = :data_pagamento,
                    status = :status,
                    observacao = :observacao
                    WHERE id = :id AND usuario_id = :usuario_id";

            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                'id'                 => $id,
                'usuario_id'         => $usuario_id,
                'fornecedor_id'      => $fornecedor_id,
                'categoria_id'       => $categoria_id,
                'forma_pagamento_id' => $forma_pagamento_id,
                'conta_id'           => $conta_id,
                'descricao'          => $descricao,
                'valor'              => $valor,
                'data_pagamento'     => $data_pagamento,
                'status'             => $status,
                'observacao'         => $observacao
            ]);

            $_SESSION['sucesso'] = "Despesa atualizada com sucesso!";
        }
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao processar dados no banco de dados.";
    }

    header("Location: listar.php");
    exit;
}