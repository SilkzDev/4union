<?php
// pages/categorias/excluir.php
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
    $_SESSION['erro'] = "Categoria inválida para exclusão.";
    header("Location: listar.php");
    exit;
}

try {
    // 1. Busca os dados da categoria para obter o ID e o Nome
    $stmt_cat = $conexao->prepare("SELECT nome FROM categorias WHERE id = :id AND usuario_id = :usuario_id");
    $stmt_cat->execute(['id' => $id, 'usuario_id' => $usuario_id]);
    $categoria = $stmt_cat->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        $_SESSION['erro'] = "Categoria não encontrada ou você não tem permissão para excluí-la.";
        header("Location: listar.php");
        exit;
    }

    $nome_categoria = $categoria['nome'];

    // 2. Verifica se há receitas vinculadas por ID ou pelo NOME da categoria
    $sql_check_rec = "SELECT COUNT(*) FROM receitas WHERE categoria_id = :id OR categoria_id = :nome";
    $stmt_check_rec = $conexao->prepare($sql_check_rec);
    $stmt_check_rec->execute([
        'id'   => $id,
        'nome' => $nome_categoria
    ]);
    $total_receitas = (int) $stmt_check_rec->fetchColumn();

    // 3. Verifica se há despesas vinculadas (caso a tabela de despesas exista)
    $total_despesas = 0;
    try {
        $sql_check_desp = "SELECT COUNT(*) FROM despesas WHERE categoria_id = :id OR categoria_id = :nome";
        $stmt_check_desp = $conexao->prepare($sql_check_desp);
        $stmt_check_desp->execute([
            'id'   => $id,
            'nome' => $nome_categoria
        ]);
        $total_despesas = (int) $stmt_check_desp->fetchColumn();
    } catch (PDOException $e) {
        $total_despesas = 0;
    }

    $total_vinculos = $total_receitas + $total_despesas;

    // 4. Bloqueia a exclusão se houver qualquer receita/despesa vinculada
    if ($total_vinculos > 0) {
        $_SESSION['erro'] = "A categoria \"{$nome_categoria}\" não pode ser excluída pois possui {$total_vinculos} registro(s) vinculado(s).";
        header("Location: listar.php");
        exit;
    }

    // 5. Exclui a categoria com segurança
    $sql_del = "DELETE FROM categorias WHERE id = :id AND usuario_id = :usuario_id";
    $stmt_del = $conexao->prepare($sql_del);
    $stmt_del->execute([
        'id'         => $id,
        'usuario_id' => $usuario_id
    ]);

    $_SESSION['sucesso'] = "Categoria excluída com sucesso!";

} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro de banco de dados ao tentar excluir a categoria.";
}

header("Location: listar.php");
exit;