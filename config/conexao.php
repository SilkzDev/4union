<?php
// Configurações do Banco de Dados
$host    = "localhost";
$dbname  = "financeiro";
$usuario = "root";
$senha   = ""; // Coloque a senha do seu banco de dados aqui se houver

try {
    // Criação da conexão utilizando PDO (mais seguro e moderno)
    $conexao = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $usuario, $senha);
    
    // Configura o PDO para lançar exceções em caso de erros
    $conexao->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Define o modo de busca padrão para array associativo
    $conexao->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    // Em ambiente de produção, mude para exibir apenas uma mensagem genérica por segurança
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>