<?php
// pages/despesas/editar.php
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
    $sql = "SELECT * FROM despesas WHERE id = :id AND usuario_id = :usuario_id AND ativo = 'S'";
    $stmt = $conexao->prepare($sql);
    $stmt->execute(['id' => $id, 'usuario_id' => $usuario_id]);
    $despesa = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($despesa) {
        echo json_encode(['sucesso' => true, 'dados' => $despesa]);
    } else {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Despesa não encontrada.']);
    }
} catch (PDOException $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro no banco de dados: ' . $e->getMessage()]);
}