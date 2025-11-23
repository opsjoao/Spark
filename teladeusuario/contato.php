<?php
// Inclui o nosso guardião para verificar se o usuário está logado.
require_once('../../formulario-cadastro-login/verificacao.php');

// Define a URL base do projeto para links corretetos.
$url_base = '/Spark-main/';

//importar fonte
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
    <title>Spark — Contato</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="../../style.css">
    <link rel="stylesheet" href="contato.css">
</head>
<body>


    <div class="app-container">
       
       <header class="header">

       <div class="appbar">
       <button class="icon-btn" onclick="window.location.href='<?php echo $url_base; ?>teladeusuario/teladeusuario.php'" aria-label="Voltar">
       <i class="fa-solid fa-arrow-left"></i>
       </button>
       <h1>Posso te ajudar?</h1>
       </div>

       <div class="profile-card">
       <div class="avatar-icon-circle">
       <i class="fa-solid fa-headset"></i>
       </div>
       </div>
       </header>

       <main class="app-content">
       <section class="contact-info">
       <h2>Informações de Contato</h2>

       <div class="contact-item">
       <p class="label">Email</p>
       <p class="value"><i class="fas fa-envelope icon"></i> debatedecria@gmail.com</p>
       </div>

       <div class="contact-item">
       <p class="label">Telefone</p>
       <p class="value"><i class="fas fa-phone icon"></i> +55 (11) 96353-4658</p>
       </div>
       </section>
       </main>
    
            <footer class="footer">
            <p>&copy; 2022-2025 Debate de Cria. Todos os direitos reservados.</p>
        </footer>
    </div>

</body>
</html>
