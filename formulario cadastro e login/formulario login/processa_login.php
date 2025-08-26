<?php

// Detalhes de conexão com o banco de dados
$servername = "localhost"; // ou 127.0.0.1
$username = "root"; 
$password = "1234"; 
$dbname = "Spark";

// Cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Verifica se o formulário foi submetido via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitiza e recupera os dados do formulário de login
    $email = $conn->real_escape_string($_POST['email']);
    $senha_digitada = $_POST['password'];

    // Prepara a instrução SQL para buscar o usuário pelo e-mail
    $sql = "SELECT senha FROM Usuario WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($senha_hashed);
    $stmt->fetch();
    $stmt->close();

    // Verifica se um usuário foi encontrado e se a senha está correta
    if ($senha_hashed && password_verify($senha_digitada, $senha_hashed)) {
        // Login bem-sucedido
        // Redireciona para uma página de sucesso (ex: dashboard.html)
        header("Location: dashboard.html");
        exit();
    } else {
        // Login falhou (usuário não encontrado ou senha incorreta)
        // Redireciona de volta para a página de login com um erro
        header("Location: index.html?error=1");
        exit();
    }
}

$conn->close();

?>