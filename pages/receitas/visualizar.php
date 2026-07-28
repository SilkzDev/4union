<?php
// pages/receitas/visualizar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

require_once "../../config/conexao.php";

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;
$id         = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID de receita inválido.']);
    exit;
}

try {
    $sql = "SELECT r.*, 
                   c.nome AS categoria_nome, 
                   cli.nome AS cliente_nome, 
                   fp.descricao AS forma_pagamento_nome,
                   DATE_FORMAT(r.data_recebimento, '%d/%m/%Y') AS data_formatada
            FROM receitas r 
            LEFT JOIN categorias c ON r.categoria_id = c.id 
            LEFT JOIN clientes cli ON r.cliente_id = cli.id 
            LEFT JOIN formas_pagamento fp ON r.forma_pagamento_id = fp.id 
            WHERE r.id = :id AND r.usuario_id = :usuario_id";

    $stmt = $conexao->prepare($sql);
    $stmt->execute([
        'id' => $id,
        'usuario_id' => $usuario_id
    ]);

    $receita = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($receita) {
        if (empty($receita['cliente_nome'])) {
            $receita['cliente_nome'] = 'Cliente Padrão';
        }
        if (empty($receita['categoria_nome'])) {
            $receita['categoria_nome'] = 'Sem Categoria';
        }
        if (empty($receita['conta_nome'])) {
            $receita['conta_nome'] = (isset($receita['conta_id']) && $receita['conta_id'] == 2) ? 'Caixa Físico' : 'Conta Principal';
        }

        echo json_encode(['sucesso' => true, 'dados' => $receita]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Receita não encontrada.']);
    }
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao consultar banco de dados.']);
}