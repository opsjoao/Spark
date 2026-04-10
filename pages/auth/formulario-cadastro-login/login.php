<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilo para a mensagem de erro */
        .error-message {
            color: #D32F2F; /* Vermelho escuro */
            background-color: #FFCDD2; /* Fundo vermelho claro */
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px; /* Espaço antes do botão */
        }
    </style>
</head>
<body>
    <div id="form_container">
        <div id="form_header">
            <h1 id="form_title">Login</h1>
        </div>
        <form id="form" action="processa_login.php" method="POST">
            <div id="input_container">
                <div class="input-box">
                    <label class="form-label" for="email">E-mail:</label>
                    <div class="input-field">
                        <input class="form-control" type="email" id="email" name="email" required>
                    </div>
                </div>

                <div class="input-box">
                    <label class="form-label" for="password">Senha:</label>
                    <div class="input-field">
                        <input class="form-control" type="password" id="password" name="password" required>
                    </div>
                </div>
            </div>

            <?php
                // --- NOVO BLOCO PHP PARA EXIBIR O ERRO ---
                // Verifica se a URL contém o parâmetro 'erro=1'
                if (isset($_GET['error']) && $_GET['error'] == 1) {
                    // Se sim, exibe o span com a mensagem de erro
                    echo '<span class="error-message">Usuário ou senha incorretos</span>';
                }
            ?>

            <button type="submit" class="btn-default">Entrar</button>

            <p class="mt-3 text-center">
                Ainda não tem conta? <a href="../formulario-cadastro-usuario/cadastro-usuario.html">Cadastrar-se</a>
            </p>
        </form>
    </div>
</body>
</html>
