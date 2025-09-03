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

    $sql = "SELECT idUsuario, senha FROM Usuario WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id_usuario, $senha_hashed);
        $stmt->fetch();

        if (password_verify($senha_digitada, $senha_hashed)) {
            // Login bem-sucedido!
            $_SESSION['loggedin'] = true;
            $_SESSION['id_usuario'] = $id_usuario;
            $_SESSION['email_usuario'] = $email;

            // Redireciona para a tela principal
            header("Location: ../../tela-principal/telaprincipal.html");
            exit();
        }
    }
    
    // Se chegou até aqui, o login falhou
    header("Location: login.html?error=1");
    exit();
}

$conn->close();
?>