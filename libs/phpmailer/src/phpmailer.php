<?php
/**
 * PHPMailer - Carregador Unificado e Autoloader Automático (Sprint 04)
 * Este arquivo mapeia as classes principais para que você não precise usar o Composer.
 */

namespace PHPMailer\PHPMailer;

// Registra uma função de carregamento automático para as classes do PHPMailer
spl_autoload_register(function ($class) {
    // Prefixo do namespace que estamos interceptando
    $prefix = 'PHPMailer\\PHPMailer\\';

    // Diretório base onde os arquivos de classe estão localizados
    $base_dir = __DIR__ . '/';

    // Verifica se a classe chamada usa o namespace do PHPMailer
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Não é classe do PHPMailer, ignora
    }

    // Obtém o nome relativo da classe (ex: SMTP, Exception, PHPMailer)
    $relative_class = substr($class, $len);

    // Constrói o caminho completo do arquivo
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // Se o arquivo existir no diretório, faz o require
    if (file_exists($file)) {
        require_once $file;
    }
});