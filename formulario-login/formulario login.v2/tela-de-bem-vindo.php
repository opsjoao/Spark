<!-- filepath: c:\Users\Aluno\Documents\formulario cadastro eventos\tela-de-bem-vindo.php -->
<?php
session_start();

// Verifica se o nome do usuário está na sessão
if (!isset($_SESSION['nomeUsuario'])) {
    header("Location: formulario-login.html"); // Redireciona para o login se não estiver logado
    exit();
}

$nomeUsuario = $_SESSION['nomeUsuario'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bem-vindo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div id="form_container">
        <h1 id="form_title">Bem-vindo, <?php echo htmlspecialchars($nomeUsuario); ?>!</h1>
        <p>Você realizou login com sucesso.</p>
        <a href="logout.php" class="btn-default">Sair</a>
    </div>
</body>
</html>