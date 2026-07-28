<?php
// pages/despesas/excluir.php
// Exclusão lógica (RF03): a despesa não é apagada do banco, apenas marcada como inativa.
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
    $_SESSION['erro'] = "Despesa inválida para exclusão.";
    header("Location: listar.php");
    exit;
}

try {
    $sql = "UPDATE despesas SET ativo = 'N' WHERE id = :id AND usuario_id = :usuario_id";
    $stmt = $conexao->prepare($sql);
    $stmt->execute([
        'id'         => $id,
        'usuario_id' => $usuario_id
    ]);

    if ($stmt->rowCount() > 0) {
        $_SESSION['sucesso'] = "Despesa excluída com sucesso!";
    } else {
        $_SESSION['erro'] = "Despesa não encontrada ou você não tem permissão para excluí-la.";
    }

} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro ao tentar excluir a despesa no banco de dados.";
}

header("Location: listar.php");
exit;