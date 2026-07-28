<?php
// 1. Configura as namespaces e importa o carregador unificado que criamos
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/libs/phpmailer/src/phpmailer.php';
require_once __DIR__ . '/config/conexao.php';

$erro = "";
$sucesso = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email_digitado = isset($_POST['email']) ? trim($_POST['email']) : '';

    if (empty($email_digitado)) {
        $erro = "Por favor, insira o seu endereço de e-mail.";
    } elseif (!filter_var($email_digitado, FILTER_VALIDATE_EMAIL)) {
        $erro = "O formato de e-mail inserido é inválido.";
    } else {
        try {
            // 2. Busca o usuário ativo correspondente ao e-mail informado
            $stmt = $conexao->prepare("SELECT id, nome, ativo FROM usuarios WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email_digitado]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($usuario) {
                if ((int)$usuario['ativo'] !== 1) {
                    $erro = "Esta conta de usuário está desativada no sistema.";
                } else {
                    // 3. Geração do Token Criptográfico (seguro e único)
                    $token = bin2hex(random_bytes(32));
                    // O token expira em exatamente 1 hora
                    $expiracao = date("Y-m-d H:i:s", strtotime("+1 hour"));

                    // 4. Grava o token gerado na ficha do usuário no banco
                    $stmt_update = $conexao->prepare("
                        UPDATE usuarios 
                        SET token_recuperacao = :token, token_expiracao = :expiracao 
                        WHERE id = :id
                    ");
                    $stmt_update->execute([
                        'token'     => $token,
                        'expiracao' => $expiracao,
                        'id'        => $usuario['id']
                    ]);

                    // 5. Configuração e Envio do E-mail Real via SMTP Seguro do Gmail
                    $mail = new PHPMailer(true);

                    // Configurações Técnicas de Conexão
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'SEU_GMAIL_REAL@gmail.com';          // SUBISTITUA PELO SEU GMAIL REAL
                    $mail->Password   = 'SUA_SENHA_DE_APP_DE_16_DIGITOS';    // INSIRA SUA SENHA DE APP DO GOOGLE
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;
                    $mail->CharSet    = 'UTF-8';

                    // Definindo Remetente e Destinatário
                    $mail->setFrom('SEU_GMAIL_REAL@gmail.com', 'Sistema Financeiro');
                    $mail->addAddress($email_digitado, $usuario['nome']);

                    // Link dinâmico enviado para o e-mail do cliente
                    $link_redefinir = "http://localhost/financeiro/redefinir.php?token=" . $token;

                    // Formatação Visual do E-mail em HTML
                    $mail->isHTML(true);
                    $mail->Subject = 'Redefinição de Senha - Acesso Restrito';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 25px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                            <h2 style='color: #1e3c72; margin-bottom: 15px;'>Olá, " . htmlspecialchars($usuario['nome']) . "!</h2>
                            <p style='color: #475569; font-size: 16px; line-height: 1.6;'>
                                Recebemos um pedido para alterar a senha da sua conta do <strong>Sistema Financeiro</strong>. 
                                Se você realizou esta solicitação, clique no botão azul abaixo para cadastrar uma nova credencial:
                            </p>
                            <p style='text-align: center; margin: 35px 0;'>
                                <a href='$link_redefinir' style='background-color: #1e3c72; color: white; padding: 14px 28px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px; display: inline-block;'>
                                    Cadastrar Nova Senha
                                </a>
                            </p>
                            <p style='color: #e11d48; font-size: 14px; background-color: #fff1f2; padding: 10px; border-radius: 4px; border-left: 4px solid #e11d48;'>
                                <strong>Atenção:</strong> Este link é válido apenas por 1 hora. Se não solicitou a mudança, ignore este e-mail por motivos de segurança.
                            </p>
                        </div>
                    ";

                    $mail->send();
                    $sucesso = "Um link de redefinição real foi enviado para o seu e-mail!";
                }
            } else {
                // Erro genérico por segurança para evitar descoberta de e-mails de usuários cadastrados
                $erro = "Se o e-mail estiver cadastrado no sistema, você receberá uma mensagem com o link.";
            }
        } catch (Exception $e) {
            $erro = "Falha no envio do e-mail. Motivo: {$mail->ErrorInfo}";
        } catch (PDOException $e) {
            $erro = "Ocorreu um problema ao conectar com o banco de dados.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Sistema Financeiro</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { background: #ffffff; width: 100%; max-width: 420px; padding: 40px 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .brand { text-align: center; margin-bottom: 25px; }
        .brand h2 { color: #1e3c72; font-size: 25px; }
        .brand p { color: #64748b; font-size: 14px; margin-top: 5px; }
        .error { background-color: #fef2f2; color: #dc2626; padding: 12px; border-radius: 6px; border: 1px solid #fca5a5; font-size: 14px; margin-bottom: 20px; }
        .success { background-color: #f0fdf4; color: #15803d; padding: 12px; border-radius: 6px; border: 1px solid #86efac; font-size: 14px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #334155; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; outline: none; font-size: 15px; }
        .form-group input:focus { border-color: #2a5298; box-shadow: 0 0 0 3px rgba(42,82,152,0.15); }
        .btn { width: 100%; padding: 12px; background-color: #1e3c72; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
        .btn:hover { background-color: #2a5298; }
        .footer-links { text-align: center; margin-top: 25px; }
        .footer-links a { color: #1e3c72; font-size: 14px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <div class="brand">
            <h2>Esqueceu a Senha?</h2>
            <p>Enviaremos um e-mail de recuperação seguro.</p>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="success">✓ <?php echo htmlspecialchars($sucesso); ?></div>
        <?php endif; ?>

        <form action="recuperar.php" method="POST">
            <div class="form-group">
                <label for="email">E-mail de Cadastro</label>
                <input type="email" id="email" name="email" required placeholder="Digite o seu Email">
            </div>
            <button type="submit" class="btn">Enviar Instruções por E-mail</button>
        </form>

        <div class="footer-links">
            <a href="index.php">← Cancelar e Voltar</a>
        </div>
    </div>
</body>
</html>