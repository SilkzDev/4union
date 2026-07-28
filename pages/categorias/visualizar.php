<?php
// pages/categorias/visualizar.php - Visualização de Categoria
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Controle de Acesso
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
}

// Caminho absoluto dinâmico para evitar erros de diretório
require_once __DIR__ . "/../../config/conexao.php";

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    header("Location: ../../dashboard.php");
    exit;
}

try {
    $stmt = $conexao->prepare("SELECT * FROM categorias WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $categoria = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$categoria) {
        header("Location: ../../dashboard.php");
        exit;
    }
} catch (PDOException $e) {
    die("Erro ao consultar o registro no banco de dados.");
}

$cor_hex = !empty($categoria['cor']) ? $categoria['cor'] : '#2b4c7e';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Categoria</title>
    <!-- CSS Externo com a estrutura exata solicitada -->
    <link rel="stylesheet" href="../../assets/css/visualizar.css">
</head>
<body>

    <div class="card">
        <h2 class="card-title">Detalhes da Categoria</h2>

        <div class="detail-row">
            <strong>ID:</strong>
            <span>#<?= htmlspecialchars($categoria['id']) ?></span>
        </div>

        <div class="detail-row">
            <strong>Nome:</strong>
            <span><?= htmlspecialchars($categoria['nome']) ?></span>
        </div>

        <div class="detail-row">
            <strong>Tipo:</strong>
            <span><?= htmlspecialchars($categoria['tipo']) ?></span>
        </div>

        <!-- LINHA COM A COR E OS NÚMEROS/CÓDIGO QUE A GERAM -->
        <div class="detail-row">
            <strong>Cor:</strong>
            <div class="color-info">
                <span class="color-preview" style="background-color: <?= htmlspecialchars($cor_hex) ?>;"></span>
                <span class="color-code"><?= strtoupper(htmlspecialchars($cor_hex)) ?></span>
            </div>
        </div>

        <div class="detail-row">
            <strong>Status:</strong>
            <span><?= ($categoria['ativo'] ?? 'S') === 'S' ? 'Ativo' : 'Inativo' ?></span>
        </div>

        <a href="../../dashboard.php" class="btn-back">Voltar</a>
    </div>

</body>
</html>