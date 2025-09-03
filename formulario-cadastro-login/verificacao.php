<?php
// Inicia a sessão.
session_start();

// Verifica se a sessão 'loggedin' não existe ou não é verdadeira.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    // Se o usuário não estiver logado, redireciona para a página de login.
    // O caminho aqui é relativo A PARTIR DESTE ARQUIVO.
    // Como login.html está na mesma pasta, o caminho é simples.
    header("location: login.html");
    exit;
}

// Se o script continuar, significa que o usuário está logado.
// O arquivo termina aqui. Nenhum HTML é necessário.
?>