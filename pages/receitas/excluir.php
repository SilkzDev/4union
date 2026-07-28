<?php
// pages/receitas/excluir.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
}

require_once "../../config/conexao.php";

$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;
$id         = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    $_SESSION['erro'] = "Receita inválida para exclusão.";
    header("Location: listar.php");
    exit;
}

try {
    // Executa a exclusão da receita validando o ID e o usuário logado
    $sql = "DELETE FROM receitas WHERE id = :id AND usuario_id = :usuario_id";
    $stmt = $conexao->prepare($sql);
    $stmt->execute([
        'id'         => $id,
        'usuario_id' => $usuario_id
    ]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['sucesso'] = "Receita excluída com sucesso!";
    } else {
        $_SESSION['erro'] = "Receita não encontrada ou você não tem permissão para excluí-la.";
    }

} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro ao tentar excluir a receita no banco de dados.";
}

header("Location: listar.php");
exit;