<?php
// Inicia a sessão
session_start();

// Verifica se o usuário JÁ ESTÁ logado
if (isset($_SESSION['id_usuario'])) {
    // Se sim, redireciona para a tela principal
    header("Location: pages/tela-principal/telaprincipal.php");
    exit();
} else {
    // Se não, redireciona para a tela de login
    header("Location: pages/auth/formulario-cadastro-login/formulario-login/login.php");
    exit();
}
?>
