<?php

//variaveis relacionadas ao banco de dados
$servername = "localhost"; // ou 127.0.0.1
$username = "root"; 
$password = "1234"; 
$dbname = "Spark";

//cria a conexão
$conn = new mysqli($servername, $username, $password, $dbname);

//verifica a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

//verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //filtra e recupera os dados do formulário
    $nome = $conn->real_escape_string($_POST['name']);//o real_escape_string indica uma funçao de segurança
    $cpf = $conn->real_escape_string($_POST['cpf']);
    $data_nasc = $conn->real_escape_string($_POST['birthdate']);
    $email = $conn->real_escape_string($_POST['email']);
    $senha = $_POST['password']; // Senha bruta
    $confirm_senha = $_POST['confirm_password'];
    $genero = $conn->real_escape_string($_POST['gender']);

    //preenche o campo tipi automaticamente no banco, pois não possui no formulário de cadastro 
    $tipo = "Comum";

    //valida se as senhas coincidem
    if ($senha !== $confirm_senha) {
        die("As senhas não coincidem.");
    }

    //hash da senha por segurança
    $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "INSERT INTO Usuario (nome, tipo, email, senha, cpf, genero, data_nasc) VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $nome, $tipo, $email, $senha_hashed, $cpf, $genero, $data_nasc);

    //execução
    if ($stmt->execute()) {
        header('Location: login.html');
        exit();
    } else {
        echo "Erro: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();

?>