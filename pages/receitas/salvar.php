<?php
// pages/receitas/salvar.php
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
    $cliente_id         = $_POST['cliente_id'] ?? '';
    $categoria_id       = $_POST['categoria_id'] ?? '';
    $valor_raw          = $_POST['valor'] ?? '0';
    $data_recebimento   = $_POST['data_recebimento'] ?? '';
    $forma_pagamento_id = $_POST['forma_pagamento_id'] ?? '';
    $conta_id           = $_POST['conta_id'] ?? 1;
    $status             = $_POST['status'] ?? 'Pendente';
    $observacoes        = trim($_POST['observacoes'] ?? $_POST['observacao'] ?? '');

    // Validação: Impede salvar/editar sem selecionar um cliente válido
    if ($cliente_id === '' || $cliente_id === null) {
        $_SESSION['erro'] = "Selecione um cliente válido (ou 'Cliente Padrão').";
        header("Location: listar.php");
        exit;
    }

    if (empty($descricao) || empty($categoria_id) || empty($data_recebimento) || empty($forma_pagamento_id)) {
        $_SESSION['erro'] = "Preencha todos os campos obrigatórios (*).";
        header("Location: listar.php");
        exit;
    }

    // Tratamento Inteligente de Moeda (Evita alteração do valor no Banco de Dados)
    $valor_str = trim((string)$valor_raw);
    if (strpos($valor_str, ',') !== false) {
        $valor_str = str_replace('.', '', $valor_str);
        $valor_str = str_replace(',', '.', $valor_str);
    }
    $valor = (float)$valor_str;

    // 0 representa 'Cliente Padrão', IDs > 0 representam clientes cadastrados
    $cli_val = intval($cliente_id);

    try {
        if ($acao === 'cadastrar') {
            $sql = "INSERT INTO receitas (usuario_id, cliente_id, categoria_id, descricao, valor, data_recebimento, forma_pagamento_id, conta_id, status, observacao) 
                    VALUES (:usuario_id, :cliente_id, :categoria_id, :descricao, :valor, :data_recebimento, :forma_pagamento_id, :conta_id, :status, :observacao)";
            
            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                'usuario_id'         => $usuario_id,
                'cliente_id'         => $cli_val,
                'categoria_id'       => $categoria_id,
                'descricao'          => $descricao,
                'valor'              => $valor,
                'data_recebimento'   => $data_recebimento,
                'forma_pagamento_id' => $forma_pagamento_id,
                'conta_id'           => $conta_id,
                'status'             => $status,
                'observacao'         => $observacoes
            ]);

            $_SESSION['sucesso'] = "Receita cadastrada com sucesso!";
        } elseif ($acao === 'editar') {
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                $_SESSION['erro'] = "ID de receita inválido para edição.";
                header("Location: listar.php");
                exit;
            }

            $sql = "UPDATE receitas SET 
                    cliente_id = :cliente_id,
                    categoria_id = :categoria_id,
                    descricao = :descricao,
                    valor = :valor,
                    data_recebimento = :data_recebimento,
                    forma_pagamento_id = :forma_pagamento_id,
                    conta_id = :conta_id,
                    status = :status,
                    observacao = :observacao
                    WHERE id = :id AND usuario_id = :usuario_id";

            $stmt = $conexao->prepare($sql);
            $stmt->execute([
                'id'                 => $id,
                'usuario_id'         => $usuario_id,
                'cliente_id'         => $cli_val,
                'categoria_id'       => $categoria_id,
                'descricao'          => $descricao,
                'valor'              => $valor,
                'data_recebimento'   => $data_recebimento,
                'forma_pagamento_id' => $forma_pagamento_id,
                'conta_id'           => $conta_id,
                'status'             => $status,
                'observacao'         => $observacoes
            ]);

            $_SESSION['sucesso'] = "Receita atualizada com sucesso!";
        }
    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro ao processar dados no banco de dados.";
    }

    header("Location: listar.php");
    exit;
}