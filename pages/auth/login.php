<?php
// login.php (Exemplo)

session_start(); // Inicia a sessão

// ... (aqui você faria a verificação de email e senha no banco de dados) ...

// Se o login for bem-sucedido, você pega o ID do usuário do banco
$idUsuarioLogado = 1; // Exemplo: ID do usuário encontrado no banco

// E armazena na sessão
$_SESSION['idUsuario'] = $idUsuarioLogado;
$_SESSION['nomeUsuario'] = "Murilo Jackson da Silva"; // Pode guardar outros dados úteis

// Redireciona para a página principal
header("Location: ../tela-principal/telaprincipal.html");
exit();
?>