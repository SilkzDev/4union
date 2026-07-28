<?php
// pages/receitas/editar.php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão não autorizada.']);
    exit;
}

require_once "../../config/conexao.php";

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
    exit;
}

try {
    $sql = "SELECT * FROM receitas WHERE id = :id AND usuario_id = :usuario_id";
    $stmt = $conexao->prepare($sql);
    $stmt->execute(['id' => $id, 'usuario_id' => $usuario_id]);
    $receita = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($receita) {
        echo json_encode(['sucesso' => true, 'dados' => $receita]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Receita não encontrada.']);
    }
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
}