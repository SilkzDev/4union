<?php
// Inicia a sessão de forma segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se o usuário já estiver logado, redireciona diretamente para o dashboard
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header("Location: dashboard.php");
    exit;
}

// 1. Importa a conexão com o banco de dados
require_once "config/conexao.php";

$erro = "";
$sucesso = "";
$nome_digitado = "";
$email_digitado = "";

// Processa o formulário quando submetido
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    // Captura e sanitiza as entradas do utilizador
    $nome_digitado  = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $email_digitado = isset($_POST['email']) ? trim($_POST['email']) : '';
    $senha          = isset($_POST['senha']) ? $_POST['senha'] : '';
    $confirma_senha = isset($_POST['confirma_senha']) ? $_POST['confirma_senha'] : '';

    // Validações básicas do lado do servidor
    if (empty($nome_digitado) || empty($email_digitado) || empty($senha) || empty($confirma_senha)) {
        $erro = "Por favor, preencha todos os campos.";
    } elseif (!filter_var($email_digitado, FILTER_VALIDATE_EMAIL)) {
        $erro = "O formato do e-mail introduzido não é válido.";
    } elseif ($senha !== $confirma_senha) {
        $erro = "As senhas introduzidas não coincidem.";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve conter no mínimo 6 caracteres por motivos de segurança.";
    } else {
        try {
            // 2. Regra de Ouro: Evitar duplicação de e-mails (chave única)
            $stmt_check = $conexao->prepare("SELECT id FROM usuarios WHERE email = :email LIMIT 1");
            $stmt_check->execute(['email' => $email_digitado]);
            
            if ($stmt_check->fetch()) {
                $erro = "Este e-mail já se encontra registado no sistema.";
            } else {
                
                // 3. Determinar o perfil inicial do novo usuário com segurança
                // Para evitar falhas de privilégio, novos cadastros são associados por padrão como 'operador'
                $stmt_perfil = $conexao->prepare("SELECT id FROM perfis WHERE LOWER(nome) = 'operador' LIMIT 1");
                $stmt_perfil->execute();
                $perfil = $stmt_perfil->fetch();
                
                if ($perfil) {
                    $perfil_id = $perfil['id'];
                } else {
                    // Fallback de segurança: busca o primeiro ID disponível se 'operador' não estiver mapeado
                    $stmt_fallback = $conexao->query("SELECT id FROM perfis LIMIT 1");
                    $perfil_id = $stmt_fallback->fetchColumn();
                }

                if (!$perfil_id) {
                    throw new Exception("Nenhum perfil de utilizador configurado no sistema.");
                }

                // 4. Criptografia de alto nível para salvaguardar a credencial
                $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

                // 5. Inserção segura no Banco de Dados usando Prepared Statement
                $stmt_cadastro = $conexao->prepare("
                    INSERT INTO usuarios (nome, email, senha, ativo, perfil_id) 
                    VALUES (:nome, :email, :senha, :ativo, :perfil_id)
                ");

                $stmt_cadastro->execute([
                    'nome'      => $nome_digitado,
                    'email'     => $email_digitado,
                    'senha'     => $senha_hash, // Guarda apenas a hash matemática
                    'ativo'     => 1,           // Usuário criado diretamente ativo para testes
                    'perfil_id' => $perfil_id
                ]);

                $sucesso = "Conta criada com sucesso! Redirecionando para a tela de login...";
                
                // Limpa as variáveis para reiniciar o formulário
                $nome_digitado = "";
                $email_digitado = "";

                // Redireciona o utilizador após 3 segundos
                header("refresh:3;url=index.php");
            }

        } catch (PDOException $e) {
            $erro = "Erro interno no banco de dados ao processar o seu registo.";
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Sistema Financeiro</title>
    <!-- Arquivo CSS isolado -->
    <link rel="stylesheet" href="assets/css/cadastro.css">
</head>
<body>

    <div class="cadastro-container">
        <div class="brand">
            <h2>Criar Conta</h2>
            <p>Cadastre-se para acessar o Sistema Financeiro</p>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="error-message">
                <strong>⚠️ </strong> &nbsp; <?php echo htmlspecialchars($erro); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="success-message">
                <strong>✓ </strong> &nbsp; <?php echo htmlspecialchars($sucesso); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="cadastroForm">

            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" required placeholder="Ex: Ana Silva"
                       value="<?php echo htmlspecialchars($nome_digitado); ?>">
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" required placeholder="Ex: ana.silva@exemplo.com"
                       value="<?php echo htmlspecialchars($email_digitado); ?>">
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" id="senha" name="senha" required placeholder="Mínimo de 6 caracteres">
            </div>

            <div class="form-group">
                <label for="confirma_senha">Confirmar Senha</label>
                <input type="password" id="confirma_senha" name="confirma_senha" required placeholder="Confirme a sua senha">
            </div>

            <button type="submit" class="action-btn">Finalizar Registo</button>
        </form>

        <div class="footer-links">
            <a href="index.php">← Voltar para o Login</a>
        </div>
    </div>

    <!-- Arquivo JS isolado -->
    <script src="assets/js/cadastro.js"></script>
</body>
</html>