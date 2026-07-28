<?php
// 1. Importa a conexão com o banco de dados
require_once "config/conexao.php";

// 2. Definição dos usuários de teste baseados nos requisitos da Sprint
$usuarios_iniciais = [
    [
        "nome"   => "Ana Silva",
        "email"  => "ana.silva@exemplo.com",
        "senha"  => "   ",
        "ativo"  => 1,
        "perfil" => "admin" // Será convertido para o ID correto da tabela 'perfis'
    ],
    [
        "nome"   => "Pedro Santos",
        "email"  => "pedro.santos@tech.corp",
        "senha"  => "Operador#123",
        "ativo"  => 0, // Usuário bloqueado/inativo para teste de segurança
        "perfil" => "operador"
    ],
    [
        "nome"   => "Maria Oliveira",
        "email"  => "maria.oliveira@web.net",
        "senha"  => "Editor$4Union",
        "ativo"  => 1,
        "perfil" => "editor"
    ]
];

try {
    // Desativa temporariamente as restrições de chaves estrangeiras para permitir a limpeza segura
    $conexao->exec("SET FOREIGN_KEY_CHECKS = 0");
    $conexao->exec("TRUNCATE TABLE usuarios");
    $conexao->exec("SET FOREIGN_KEY_CHECKS = 1");

    // 3. Garante que os registros necessários existam na tabela 'perfis' antes de associá-los
    // (Ajustamos para que 'admin', 'operador' e 'editor' tenham correspondência direta)
    $perfis_necessarios = ['admin', 'operador', 'editor'];
    $mapeamento_perfis = [];

    foreach ($perfis_necessarios as $nome_perfil) {
        // Verifica se o perfil já existe na tabela 'perfis'
        $stmt_perfil = $conexao->prepare("SELECT id FROM perfis WHERE LOWER(nome) = :nome LIMIT 1");
        $stmt_perfil->execute(['nome' => $nome_perfil]);
        $perfil_existente = $stmt_perfil->fetch();

        if ($perfil_existente) {
            $mapeamento_perfis[$nome_perfil] = $perfil_existente['id'];
        } else {
            // Se o perfil não existir, insere-o dinamicamente na tabela 'perfis'
            $stmt_insere = $conexao->prepare("INSERT INTO perfis (nome) VALUES (:nome)");
            $stmt_insere->execute(['nome' => ucfirst($nome_perfil)]);
            $mapeamento_perfis[$nome_perfil] = $conexao->lastInsertId();
        }
    }

    // Prepara a instrução SQL de inserção na tabela 'usuarios' usando a coluna correta de chave estrangeira (perfil_id)
    $stmt = $conexao->prepare("
        INSERT INTO usuarios (nome, email, senha, ativo, perfil_id) 
        VALUES (:nome, :email, :senha, :ativo, :perfil_id)
    ");

    echo "<h2>Alimentando o Banco de Dados (4 Union)</h2>";
    echo "<ul>";

    foreach ($usuarios_iniciais as $u) {
        // Obtém o ID do perfil correspondente a partir do mapeamento
        $perfil_id = $mapeamento_perfis[$u["perfil"]];

        // Gera o hash seguro da senha
        $senha_hash = password_hash($u["senha"], PASSWORD_DEFAULT);

        // Executa a inserção associando os valores aos parâmetros correspondentes
        $stmt->execute([
            "nome"      => $u["nome"],
            "email"     => $u["email"],
            "senha"     => $senha_hash,
            "ativo"     => $u["ativo"],
            "perfil_id" => $perfil_id
        ]);

        echo "<li>Usuário <strong>" . htmlspecialchars($u["nome"]) . "</strong> cadastrado com sucesso!<br>";
        echo "Email: <span style='color:#3a7bd5;'>" . htmlspecialchars($u["email"]) . "</span><br>";
        echo "Perfil ID: <span style='color:#10b981;'>" . $perfil_id . " (" . ucfirst($u["perfil"]) . ")</span><br>";
        echo "Senha de teste: <span style='color:#f59e0b;'>" . htmlspecialchars($u["senha"]) . "</span></li><br>";
    }

    echo "</ul>";
    echo "<p style='color:green; font-weight:bold;'>✓ Banco de dados populado com sucesso respeitando a integridade das tabelas relacionais!</p>";

} catch (PDOException $e) {
    // Garante que a checagem de integridade seja reativada mesmo em caso de erro grave
    $conexao->exec("SET FOREIGN_KEY_CHECKS = 1");
    die("Erro ao popular banco de dados: " . $e->getMessage());
}