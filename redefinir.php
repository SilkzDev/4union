<?php
require_once "config/conexao.php";

$erro = "";
$sucesso = "";

// 1. Coleta o token recebido pelo link de e-mail (Parâmetro GET)
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    die("<h3 style='text-align:center; margin-top:50px; color:#dc2626; font-family:sans-serif;'>Link inválido: Token de acesso ausente.</h3>");
}

try {
    // 2. Busca o usuário que possui este token de recuperação específico
    $stmt = $conexao->prepare("
        SELECT id, token_expiracao 
        FROM usuarios 
        WHERE token_recuperacao = :token 
        LIMIT 1
    ");
    $stmt->execute(['token' => $token]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        die("<h3 style='text-align:center; margin-top:50px; color:#dc2626; font-family:sans-serif;'>Erro: Link inválido ou token já utilizado.</h3>");
    }

    // 3. Validação temporal: Verifica se o link já passou de 1 hora de existência
    $agora = date("Y-m-d H:i:s");
    if ($agora > $usuario['token_expiracao']) {
        die("<h3 style='text-align:center; margin-top:50px; color:#dc2626; font-family:sans-serif;'>Erro: Este link expirou devido ao limite de tempo (1 hora).</h3><p style='text-align:center;'><a href='recuperar.php'>Solicitar novo link</a></p>");
    }

} catch (PDOException $e) {
    die("Erro interno no processamento de validação.");
}

// 4. Caso o formulário seja enviado com as novas senhas
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nova_senha = isset($_POST['nova_senha']) ? $_POST['nova_senha'] : '';
    $confirma_senha = isset($_POST['confirma_senha']) ? $_POST['confirma_senha'] : '';

    if (empty($nova_senha) || empty($confirma_senha)) {
        $erro = "Por favor, preencha todos os campos obrigatórios.";
    } elseif ($nova_senha !== $confirma_senha) {
        $erro = "As senhas inseridas são diferentes.";
    } elseif (strlen($nova_senha) < 6) {
        $erro = "A senha deve conter no mínimo 6 caracteres por segurança.";
    } else {
        try {
            // 5. Criptografia padrão ouro para a nova senha
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

            // 6. Atualiza a senha no banco de dados e anula o token para evitar reuso
            $stmt_update = $conexao->prepare("
                UPDATE usuarios 
                SET senha = :senha, token_recuperacao = NULL, token_expiracao = NULL 
                WHERE id = :id
            ");
            $stmt_update->execute([
                'senha' => $senha_hash,
                'id'    => $usuario['id']
            ]);

            $sucesso = "Excelente! Sua nova senha foi atualizada com sucesso.";
            
            // Redireciona o usuário para o index.php em 3 segundos
            header("refresh:3;url=index.php");

        } catch (PDOException $e) {
            $erro = "Erro ao gravar a nova senha no banco de dados.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha - Sistema Financeiro</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { background: #ffffff; width: 100%; max-width: 400px; padding: 40px 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        h2 { color: #1e3c72; margin-bottom: 10px; text-align: center; font-size: 24px; }
        p.desc { text-align: center; color: #64748b; font-size: 14px; margin-bottom: 25px; }
        .error { background-color: #fef2f2; color: #dc2626; padding: 12px; border-radius: 6px; border: 1px solid #fca5a5; font-size: 14px; margin-bottom: 20px; }
        .success { background-color: #f0fdf4; color: #15803d; padding: 15px; border-radius: 6px; border: 1px solid #86efac; font-size: 14px; margin-bottom: 20px; text-align: center; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #334155; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 15px; outline: none; }
        .form-group input:focus { border-color: #1e3c72; box-shadow: 0 0 0 3px rgba(30, 60, 114, 0.15); }
        .btn { width: 100%; padding: 12px; background-color: #10b981; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background-color: #059669; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Criar Nova Senha</h2>
        <p class="desc">A sua credencial antiga será substituída com segurança.</p>

        <?php if (!empty($erro)): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="success">
                <strong>✓ <?php echo $sucesso; ?></strong><br><br>
                <span style="font-size:12px; color:#475569;">A redirecionar para a página de login...</span>
            </div>
        <?php else: ?>
            <form action="redefinir.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                <div class="form-group">
                    <label for="nova_senha">Nova Senha</label>
                    <input type="password" id="nova_senha" name="nova_senha" required placeholder="Mínimo de 6 dígitos">
                </div>
                
                <div class="form-group">
                    <label for="confirma_senha">Confirmar Nova Senha</label>
                    <input type="password" id="confirma_senha" name="confirma_senha" required placeholder="Repita a senha">
                </div>
                
                <button type="submit" class="btn">Atualizar Minha Senha</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>