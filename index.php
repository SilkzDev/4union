<?php
// Inicia a sessão de forma segura (essencial para persistência de estado HTTP)
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
$email_digitado = "";

// Verifica se o formulário de login foi submetido via POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Captura e limpa as credenciais digitadas
    $email_digitado = isset($_POST['email']) ? trim($_POST['email']) : '';
    $senha_digitada = isset($_POST['senha']) ? $_POST['senha'] : '';

    if (empty($email_digitado) || empty($senha_digitada)) {
        $erro = "Por favor, preencha todos os campos.";
    } else {
        try {
            // 2. Consulta a tabela 'usuarios' usando Prepared Statement contra SQL Injection
            $stmt = $conexao->prepare("
                SELECT u.*, p.nome AS nome_perfil 
                FROM usuarios u
                LEFT JOIN perfis p ON u.perfil_id = p.id
                WHERE u.email = :email 
                LIMIT 1
            ");

            $stmt->execute(['email' => $email_digitado]);
            $usuario_db = $stmt->fetch(PDO::FETCH_ASSOC);

            // 3. Lógica de Decisão e Autenticação
            if ($usuario_db) {

                if ((int)$usuario_db['ativo'] !== 1) {
                    $erro = "Acesso negado: Este usuário está desativado no sistema.";
                }
                elseif (password_verify($senha_digitada, $usuario_db['senha'])) {

                    session_regenerate_id(true);

                    $_SESSION['usuario_id']     = $usuario_db['id'];
                    $_SESSION['usuario_nome']   = $usuario_db['nome'];
                    $_SESSION['usuario_email']  = $usuario_db['email'];
                    $_SESSION['usuario_perfil'] = strtolower($usuario_db['nome_perfil']);
                    $_SESSION['logado']         = true;

                    header("Location: dashboard.php");
                    exit;

                } else {
                    $erro = "E-mail ou senha incorretos.";
                }
            } else {
                $erro = "E-mail ou senha incorretos.";
            }

        } catch (PDOException $e) {
            $erro = "Erro interno no servidor de banco de dados.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Financeiro</title>
    <!-- Inclusão do CSS Externo -->
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

    <!-- Container geral para alinhar a logo acima do card -->
    <div class="login-wrapper">

        <!-- Logo colocada FORA do card branco -->
        <div class="brand">
            <img src="img/logo2.png" alt="4 Union Logo" class="login-logo">
        </div>

        <!-- Card Branco de Login -->
        <div class="login-container">

            <?php if (!empty($erro)): ?>
                <div class="error-message">
                    <strong>⚠️ </strong> &nbsp; <?php echo htmlspecialchars($erro); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" id="loginForm">

                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" required placeholder="Digite seu e-mail"
                           value="<?php echo htmlspecialchars($email_digitado); ?>">
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required placeholder="Digite sua senha">
                </div>

                <button type="submit" class="action-btn">Entrar</button>
                
                <a href="cadastro.php" class="register-btn">Criar Nova Conta</a>
            </form>

            <div class="footer-links">
                <a href="recuperar.php">Esqueci minha senha</a>
            </div>
        </div>

    </div>

    <!-- Inclusão do JS Externo -->
    <script src="assets/js/index.js"></script>
</body>
</html>