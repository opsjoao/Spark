<?php
// INICIE A SESSÃO NO TOPO DE TUDO!
session_start();

// Detalhes de conexão com o banco de dados
$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "Spark";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $senha_digitada = $_POST['password'];

    // Busca o usuário pelo e-mail
    $sql = "SELECT idUsuario, nome, senha FROM Usuario WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // Verifica a senha com hash
        if (password_verify($senha_digitada, $usuario['senha'])) {
            // Login bem-sucedido!
            $_SESSION['loggedin'] = true;
            $_SESSION['id_usuario'] = $usuario['idUsuario'];
            $_SESSION['nome_usuario'] = $usuario['nome'];

            // --- CORREÇÃO APLICADA AQUI ---
            // Usando o caminho absoluto para o redirecionamento, baseado no que funcionou no seu teste.
            // Se o seu teste mostrou que você NÃO precisa do /Spark-main/, remova-o daqui.
            $redirect_url = '/Spark-main/tela-principal/telaprincipal.html';
            header("Location: " . $redirect_url);
            exit();

        }
    }
    
    // Se chegou até aqui, o login falhou
    // Redireciona de volta para a PÁGINA DE LOGIN com o parâmetro de erro.
    header("Location: login.php?error=1");
    exit();
}

$conn->close();
?>