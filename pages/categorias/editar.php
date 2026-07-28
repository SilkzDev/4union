<?php
// pages/categorias/editar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
}

require_once "../../config/conexao.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $stmt = $conexao->prepare("SELECT * FROM categorias WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cat) {
        $_SESSION['erro'] = "Registro não encontrado.";
        header("Location: index.php");
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['erro'] = "Erro na busca do registro.";
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Categoria</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Arquivo CSS isolado -->
    <link rel="stylesheet" href="../../assets/css/editar.css">
</head>
<body>

<div class="card">
    <h3 class="card-title">Editar Categoria #<?= $cat['id'] ?></h3>
    
    <form action="salvar.php" method="POST" id="formCategoria">
        <input type="hidden" name="acao" value="editar">
        <input type="hidden" name="id_categoria" value="<?= $cat['id'] ?>">

        <div class="form-group">
            <label>Nome da Categoria *</label>
            <input type="text" id="nome" name="nome" value="<?= htmlspecialchars($cat['nome']) ?>" required>
        </div>

        <div class="form-group">
            <label>Tipo Financeiro *</label>
            <select id="tipo" name="tipo" required>
                <option value="Receita" <?= $cat['tipo'] === 'Receita' ? 'selected' : '' ?>>Receita</option>
                <option value="Despesa" <?= $cat['tipo'] === 'Despesa' ? 'selected' : '' ?>>Despesa</option>
            </select>
        </div>

        <div class="form-group">
            <label>Cor Identificadora</label>
            <input type="color" name="cor" value="<?= htmlspecialchars($cat['cor']) ?>" class="color-input">
        </div>

        <div class="form-group">
            <label>Ícone</label>
            <select name="icone">
                <option value="fa-tag" <?= $cat['icone'] === 'fa-tag' ? 'selected' : '' ?>>Etiqueta Padrão</option>
                <option value="fa-basket-shopping" <?= $cat['icone'] === 'fa-basket-shopping' ? 'selected' : '' ?>>Alimentação</option>
                <option value="fa-car" <?= $cat['icone'] === 'fa-car' ? 'selected' : '' ?>>Transporte</option>
                <option value="fa-wallet" <?= $cat['icone'] === 'fa-wallet' ? 'selected' : '' ?>>Salário / Renda</option>
            </select>
        </div>

        <div class="form-group checkbox-group">
            <input type="checkbox" name="ativo" value="S" <?= $cat['ativo'] === 'S' ? 'checked' : '' ?>>
            <label>Categoria Ativa</label>
        </div>

        <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Atualizar Alterações</button>
        <a href="index.php" class="btn-cancel">Cancelar</a>
    </form>
</div>

<!-- Arquivo JS isolado -->
<script src="../../assets/js/categorias.js"></script>
</body>
</html>