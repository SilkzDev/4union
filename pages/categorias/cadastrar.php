<?php
// pages/categorias/cadastrar.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Categoria</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Arquivo CSS isolado -->
    <link rel="stylesheet" href="../../assets/css/cadastrar.css">
</head>
<body>

<div class="card">
    <h3 class="card-title">Nova Categoria</h3>
    
    <form action="salvar.php" method="POST" id="formCategoria">
        <input type="hidden" name="acao" value="cadastrar">

        <div class="form-group">
            <label>Nome da Categoria *</label>
            <input type="text" id="nome" name="nome" required>
        </div>

        <div class="form-group">
            <label>Tipo Financeiro *</label>
            <select id="tipo" name="tipo" required>
                <option value="">-- Selecione --</option>
                <option value="Receita">Receita</option>
                <option value="Despesa">Despesa</option>
            </select>
        </div>

        <div class="form-group">
            <label>Cor Identificadora</label>
            <input type="color" name="cor" value="#2b4c7e" class="color-input">
        </div>

        <div class="form-group">
            <label>Ícone (FontAwesome)</label>
            <select name="icone">
                <option value="fa-tag">Etiqueta Padrão</option>
                <option value="fa-basket-shopping">Alimentação</option>
                <option value="fa-car">Transporte</option>
                <option value="fa-wallet">Salário / Renda</option>
            </select>
        </div>

        <div class="form-group checkbox-group">
            <input type="checkbox" name="ativo" value="S" checked>
            <label>Categoria Ativa</label>
        </div>

        <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Salvar Registro</button>
        <a href="index.php" class="btn-cancel">Cancelar</a>
    </form>
</div>

<!-- Arquivo JS isolado -->
<script src="../../assets/js/categorias.js"></script>
</body>
</html>