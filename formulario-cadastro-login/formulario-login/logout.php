<?php
// 1. Inicia a sessão
session_start();

// 2. Apaga todas as variáveis da sessão
$_SESSION = array();

// 3. Destrói a sessão
session_destroy();

// 4. Redireciona o usuário para a página de login
// Use o caminho absoluto que funciona para o seu projeto
header("Location: /Spark-main/formulario-login/login.php");
exit();
?>