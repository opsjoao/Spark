<?php require_once('../formulario-cadastro-login/verificacao.php'); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Amigo - Spark</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="src/perfil.css">
</head>
<body>
    <header class="appbar">
        <a href="perfil.php" class="back-button"><i class="fa-solid fa-chevron-left"></i></a>
        <h1>Adicionar Amigo</h1>
    </header>

    <main class="app-content">
        <div class="search-box">
            <input type="text" id="search-input" placeholder="Digite o username (ex: @joao123)">
            <button id="search-button"><i class="fa-solid fa-magnifying-glass"></i></button>
        </div>
        
        <div id="search-results-container">
            </div>
    </main>
    
    <script src="src/perfil.js"></script>
</body>
</html>