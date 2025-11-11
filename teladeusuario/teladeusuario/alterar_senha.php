<?php
// Inclui o nosso guardião para verificar se o usuário está logado.
require_once('../formulario-cadastro-login/verificacao.php');

// Define a URL base do projeto para links corretetos.
$url_base = '/Spark-main/';

//importar fonte
include('../header.php');

// Define a URL para onde o formulário enviará os dados.
$processar_alteracao_url = $url_base . 'teladeusuario/processar_alteracao_senha.php';

// Inicia a sessão se ainda não foi iniciada para buscar mensagens de erro/sucesso.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Spark — Alterar Senha</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="alterar_senha.css">

</head>
<body>
    <header class="appbar">
        <button class="icon-btn" onclick="window.location.href='<?php echo $url_base; ?>teladeusuario/teladeusuario.php'" aria-label="Voltar">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <h1>Alterar Senha</h1>
        <div style="width: 24px;"></div> </header>

    <main class="app-content">
        <form action="<?php echo $processar_alteracao_url; ?>" method="POST">
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="message error">
                    <?php 
                        echo htmlspecialchars($_SESSION['error_message']); 
                        unset($_SESSION['error_message']); // Limpa a mensagem após exibir
                    ?>
                </div>
            <?php endif; ?>

            <div class="form-field-group">
                <label for="senha_atual">Senha Atual</label>
                <input type="password" id="senha_atual" name="senha_atual" required>
            </div>

            <div class="form-field-group">
                <label for="nova_senha">Nova Senha</label>
                <input type="password" id="nova_senha" name="nova_senha" required>
            </div>
            
            <div class="form-field-group">
                <label for="confirmar_senha">Confirmar Nova Senha</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required>
            </div>

            <button type="submit" class="btn-primary-large">Alterar Senha</button>
        </form>
    </main>
    
</body>
</html>