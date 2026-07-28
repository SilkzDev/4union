<?php
// pages/despesas/visualizar.php
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
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID de despesa inválido.']);
    exit;
}

try {
    $sql = "SELECT d.*,
                   c.nome AS categoria_nome,
                   f.nome_fantasia AS fornecedor_nome,
                   fp.descricao AS forma_pagamento_nome,
                   ct.nome AS conta_nome,
                   DATE_FORMAT(d.data_pagamento, '%d/%m/%Y') AS data_formatada
            FROM despesas d
            LEFT JOIN categorias c ON d.categoria_id = c.id
            LEFT JOIN fornecedores f ON d.fornecedor_id = f.id
            LEFT JOIN formas_pagamento fp ON d.forma_pagamento_id = fp.id
            LEFT JOIN contas ct ON d.conta_id = ct.id
            WHERE d.id = :id AND d.usuario_id = :usuario_id";

    $stmt = $conexao->prepare($sql);
    $stmt->execute([
        'id' => $id,
        'usuario_id' => $usuario_id
    ]);

    $despesa = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($despesa) {
        if (empty($despesa['fornecedor_nome'])) {
            $despesa['fornecedor_nome'] = 'Fornecedor não informado';
        }
        if (empty($despesa['categoria_nome'])) {
            $despesa['categoria_nome'] = 'Sem Categoria';
        }
        if (empty($despesa['conta_nome'])) {
            $despesa['conta_nome'] = 'Conta Principal';
        }

        echo json_encode(['sucesso' => true, 'dados' => $despesa]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Despesa não encontrada.']);
    }
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao consultar banco de dados.']);
}