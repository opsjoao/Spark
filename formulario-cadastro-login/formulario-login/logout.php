<?php
// 1. É fundamental iniciar a sessão para poder manipulá-la.
session_start();

// 2. Limpa todas as variáveis da sessão (boa prática).
$_SESSION = array();

// 3. Destrói a sessão. Esta é a ação principal do logout.
session_destroy();

// 4. Redireciona o usuário para a página de login após destruir a sessão.
header("location: login.html");
exit;
?>