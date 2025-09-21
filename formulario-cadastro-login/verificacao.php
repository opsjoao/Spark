<?php
// 1. Inicia a sessão
// Esta função precisa ser a PRIMEIRA coisa no script, antes de qualquer HTML.
session_start();

// 2. Verifica se a sessão 'id_usuario' NÃO foi criada
// Usamos 'id_usuario' porque é isso que o seu 'processa_login.php' está criando.
if (!isset($_SESSION['id_usuario'])) {
    
    // 3. Se não estiver logado, destrói qualquer resquício de sessão
    session_destroy();
    
    // 4. Redireciona para a página de login com uma mensagem de erro (opcional)
    // Usando o caminho absoluto para garantir que sempre funcione.
    header("Location: /Spark-main/formulario-login/login.php?erro=restrito");
    exit(); // Encerra o script para garantir que o redirecionamento aconteça
}

// Se o script continuar, significa que o usuário está logado e pode ver a página.
?>
