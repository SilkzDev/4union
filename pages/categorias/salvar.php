<?php
// pages/categorias/salvar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
}

require_once "../../config/conexao.php";

// Captura do ID do usuário com suporte a múltiplos nomes de variável de sessão
$usuario_id = $_SESSION['usuario_id'] ?? $_SESSION['id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['user_id'] ?? null;

if (!$usuario_id) {
    $_SESSION['erro'] = "Sessão inválida. Por favor, faça login novamente.";
    header("Location: ../../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao  = $_POST['acao'] ?? '';
    $id    = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    $nome  = trim($_POST['nome'] ?? '');
    $tipo  = trim($_POST['tipo'] ?? '');
    $cor   = trim($_POST['cor'] ?? '#1e3c72');
    $icone = trim($_POST['icone'] ?? 'fa-tag');

    // CORREÇÃO: Captura direta do valor do <select> ('S' ou 'N')
    $ativo = trim($_POST['ativo'] ?? 'S');
    if (!in_array($ativo, ['S', 'N'])) {
        $ativo = 'S';
    }

    // 1. Validação de campos obrigatórios
    if (empty($nome) || empty($tipo)) {
        $_SESSION['erro'] = "Preencha os campos obrigatórios (Nome e Tipo).";
        header("Location: listar.php");
        exit;
    }

    try {
        // 2. Validação de duplicidade restrita ao usuário logado
        if ($acao === 'cadastrar') {
            $stmtCheck = $conexao->prepare("
                SELECT id FROM categorias 
                WHERE usuario_id = :usuario_id AND LOWER(nome) = LOWER(:nome) 
                LIMIT 1
            ");
            $stmtCheck->execute([
                'usuario_id' => $usuario_id,
                'nome'       => $nome
            ]);
        } else if ($acao === 'editar' && $id) {
            $stmtCheck = $conexao->prepare("
                SELECT id FROM categorias 
                WHERE usuario_id = :usuario_id AND LOWER(nome) = LOWER(:nome) AND id != :id 
                LIMIT 1
            ");
            $stmtCheck->execute([
                'usuario_id' => $usuario_id,
                'nome'       => $nome,
                'id'         => $id
            ]);
        }

        if (isset($stmtCheck) && $stmtCheck->fetch()) {
            $_SESSION['erro'] = "Você já possui uma categoria chamada \"<strong>" . htmlspecialchars($nome) . "</strong>\".";
            header("Location: listar.php");
            exit;
        }

        // 3. Persistência dos Dados
        if ($acao === 'cadastrar') {
            $stmt = $conexao->prepare("
                INSERT INTO categorias (usuario_id, nome, tipo, cor, icone, ativo)
                VALUES (:usuario_id, :nome, :tipo, :cor, :icone, :ativo)
            ");
            $stmt->execute([
                'usuario_id' => $usuario_id,
                'nome'       => $nome,
                'tipo'       => $tipo,
                'cor'        => $cor,
                'icone'      => $icone,
                'ativo'      => $ativo
            ]);

            $_SESSION['sucesso'] = "Categoria cadastrada com sucesso!";

        } else if ($acao === 'editar' && $id) {
            $stmt = $conexao->prepare("
                UPDATE categorias
                SET nome = :nome, tipo = :tipo, cor = :cor, icone = :icone, ativo = :ativo
                WHERE id = :id AND usuario_id = :usuario_id
            ");
            $stmt->execute([
                'nome'       => $nome,
                'tipo'       => $tipo,
                'cor'        => $cor,
                'icone'      => $icone,
                'ativo'      => $ativo,
                'id'         => $id,
                'usuario_id' => $usuario_id
            ]);

            $_SESSION['sucesso'] = "Categoria atualizada com sucesso!";
        }

        header("Location: listar.php");
        exit;

    } catch (PDOException $e) {
        $_SESSION['erro'] = "Erro de banco de dados ao salvar a categoria.";
        header("Location: listar.php");
        exit;
    }
} else {
    header("Location: listar.php");
    exit;
}