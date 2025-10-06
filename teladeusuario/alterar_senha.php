<?php
// Inclui o nosso guardião para verificar se o usuário está logado.
require_once('../formulario-cadastro-login/verificacao.php');

// Define a URL base do projeto para links corretos.
$url_base = '/Spark-main/';

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
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Spark — Alterar Senha</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $url_base; ?>teladeusuario/teladeusuario.css" />
    <style>
        /* Estilos para mensagens de feedback */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 10px;
            font-weight: 500;
            text-align: center;
        }
        .message.error {
            background-color: #FEE2E2;
            color: #B91C1C;
        }
    </style>
</head>
<body>
    <header class="appbar edit-appbar">
        <button class="icon-btn" onclick="window.location.href='<?php echo $url_base; ?>teladeusuario/teladeusuario.php'" aria-label="Voltar"><i class="fa-solid fa-arrow-left"></i></button>
        <h1>Alterar Senha</h1>
    </header>

    <main class="app-content">
        <form action="<?php echo $processar_alteracao_url; ?>" method="POST">
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="message error">
                    <?php 
                        echo $_SESSION['error_message']; 
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
    
    <nav class="bottombar">
        <button class="nav-btn" onclick="window.location.href='<?php echo $url_base; ?>TelaPerfils/perfil.html'">
            <i class="fa-solid fa-users"></i>
        </button>
        <button class="nav-btn" onclick="window.location.href='<?php echo $url_base; ?>tela-atividades/atividades.php'">
            <i class="fa-solid fa-person-walking"></i>
        </button>
        <button class="nav-btn" onclick="window.location.href='<?php echo $url_base; ?>tela-principal/telaprincipal.php'">
            <i class="fa-solid fa-house"></i>
        </button>
        <button class="nav-btn" onclick="window.location.href='<?php echo $url_base; ?>telaFavoritos/index.html'">
            <i class="fa-solid fa-star"></i>
        </button>
        <button class="nav-btn active">
            <i class="fa-solid fa-user"></i>
        </button>
    </nav>
</body>
</html>