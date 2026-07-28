<?php
// Envio de e-mail via Gmail SMTP, usando os arquivos do PHPMailer incluídos
// manualmente em libs/ (sem precisar de Composer).

require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --- Credenciais do Gmail que vai ENVIAR o e-mail ---
// IMPORTANTE: não suba este arquivo com as credenciais reais para o GitHub.
// Adicione "config/mailer.php" no .gitignore.
define('SMTP_EMAIL', 'SEU_EMAIL_AQUI@gmail.com');       // <-- trocar
define('SMTP_APP_PASSWORD', 'SENHA_DE_APP_AQUI');       // <-- trocar (senha de app de 16 dígitos)

// Chave secreta usada para assinar os tokens de redefinição de senha.
// Pode ser qualquer string longa e aleatória — troque por uma sua.
define('APP_SECRET', 'troque-por-uma-string-aleatoria-bem-grande-123456');


function enviarEmailRecuperacao($destinatario, $nomeDestinatario, $linkRedefinicao) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_EMAIL;
        $mail->Password   = SMTP_APP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(SMTP_EMAIL, 'Sistema Financeiro - 4 Union');
        $mail->addAddress($destinatario, $nomeDestinatario);

        $mail->isHTML(true);
        $mail->Subject = 'Redefinição de senha - Sistema Financeiro 4 Union';
        $mail->Body    = "
            <div style='font-family: Segoe UI, sans-serif; max-width: 480px; margin: auto;'>
                <h2 style='color:#1e3c72;'>Redefinição de senha</h2>
                <p>Olá, <strong>{$nomeDestinatario}</strong>!</p>
                <p>Recebemos uma solicitação para redefinir a senha da sua conta no Sistema Financeiro 4 Union.</p>
                <p>Clique no botão abaixo para criar uma nova senha. Este link é válido por 1 hora.</p>
                <p style='text-align:center; margin: 30px 0;'>
                    <a href='{$linkRedefinicao}' style='background:#1e3c72; color:white; padding:12px 24px; border-radius:6px; text-decoration:none; font-weight:bold;'>
                        Redefinir minha senha
                    </a>
                </p>
                <p style='font-size:13px; color:#64748b;'>Se você não solicitou isso, pode ignorar este e-mail com segurança.</p>
            </div>
        ";
        $mail->AltBody = "Acesse o link para redefinir sua senha: {$linkRedefinicao}";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log("Erro ao enviar e-mail: " . $mail->ErrorInfo);
        return false;
    }
}


// ---------------------------------------------------------------------
// Token "stateless": não precisa salvar nada no banco de dados.
// O token carrega o id do usuário + validade, e é assinado com HMAC.
// Se alguém alterar o conteúdo, a assinatura não bate e o token é recusado.
// ---------------------------------------------------------------------

function gerarTokenRedefinicao($usuarioId) {
    $payload = base64_encode(json_encode([
        'id'  => $usuarioId,
        'exp' => time() + 3600, // válido por 1 hora
    ]));
    $assinatura = hash_hmac('sha256', $payload, APP_SECRET);
    return $payload . '.' . $assinatura;
}

/**
 * Retorna o id do usuário se o token for válido e não estiver expirado,
 * ou null se for inválido/expirado/adulterado.
 */
function validarTokenRedefinicao($token) {
    $partes = explode('.', $token);
    if (count($partes) !== 2) {
        return null;
    }

    [$payload, $assinatura] = $partes;

    $assinaturaEsperada = hash_hmac('sha256', $payload, APP_SECRET);
    if (!hash_equals($assinaturaEsperada, $assinatura)) {
        return null; // token adulterado
    }

    $dados = json_decode(base64_decode($payload), true);
    if (!$dados || !isset($dados['id'], $dados['exp'])) {
        return null;
    }

    if (time() > $dados['exp']) {
        return null; // expirado
    }

    return $dados['id'];
}
