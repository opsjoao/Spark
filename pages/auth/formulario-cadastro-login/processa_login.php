<?php
// Inicia a sessão no topo de tudo!
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
    
    // Verifica se encontrou algum usuário
    if ($result->num_rows === 1) {
        $usuario = $result->fetch_assoc();
        
        // Verifica se a senha digitada corresponde à senha com hash no banco
        if (password_verify($senha_digitada, $usuario['senha'])) {
            // Login bem-sucedido!
            $_SESSION['loggedin'] = true;
            $_SESSION['id_usuario'] = $usuario['idUsuario'];
            $_SESSION['nome_usuario'] = $usuario['nome'];

            // Atualiza o último login (opcional)
            $stmt_update = $conn->prepare("UPDATE Usuario SET ultimo_login = NOW() WHERE idUsuario = ?");
            $stmt_update->bind_param("i", $usuario['idUsuario']);
            $stmt_update->execute();

            // ====================================================================
            // CORREÇÃO APLICADA AQUI: Redireciona para o caminho absoluto correto
            // ====================================================================
            header("Location: /Spark-main/tela-principal/telaprincipal.php");
            exit();

        }
    }
    
    // Se chegou até aqui, o login falhou.
    // Redireciona de volta para a página de login com o parâmetro de erro.
    header("Location: login.php?error=1");
    exit();
}

$conn->close();
?>
