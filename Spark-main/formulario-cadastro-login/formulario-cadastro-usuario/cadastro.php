<?php
// Inclui o arquivo de conexão
require_once '../conexao.php';

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Filtra e recupera os dados do formulário
    $nome = $conn->real_escape_string($_POST['name']);
    $username = $conn->real_escape_string($_POST['username']); // NOVO CAMPO
    $cpf = $conn->real_escape_string($_POST['cpf']);
    $data_nasc = $conn->real_escape_string($_POST['birthdate']);
    $email = $conn->real_escape_string($_POST['email']);
    $senha = $_POST['password']; // Senha bruta
    $confirm_senha = $_POST['confirm_password'];
    $genero = $_POST['gender'] ?? 'Não informado'; // Define um padrão caso não seja enviado
    $tipo = "comum"; // Define o tipo padrão como 'comum'

    // Valida se as senhas coincidem
    if ($senha !== $confirm_senha) {
        die("As senhas não coincidem. Por favor, tente novamente.");
    }

    // Hash da senha por segurança
    $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);

    // SQL ATUALIZADO para incluir a coluna 'username'
    $sql = "INSERT INTO Usuario (nome, username, tipo, email, senha, cpf, genero, data_nasc) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    
    // BIND PARAM ATUALIZADO para incluir a nova variável 'username'
    // A ordem deve ser EXATAMENTE a mesma do INSERT: ssssssss (8 strings)
    $stmt->bind_param("ssssssss", $nome, $username, $tipo, $email, $senha_hashed, $cpf, $genero, $data_nasc);

    // Execução
    if ($stmt->execute()) {
        // Redireciona para a tela de login após o sucesso
        header('Location: ../formulario-login/login.php');
        exit();
    } else {
        // Verifica se o erro é de entrada duplicada para dar uma mensagem mais amigável
        if ($conn->errno == 1062) {
            echo "Erro: E-mail, CPF ou Nome de Usuário já cadastrado. <a href='javascript:history.back()'>Tente novamente</a>.";
        } else {
            echo "Erro ao cadastrar: " . $stmt->error;
        }
    }

    $stmt->close();
}

$conn->close();
?>
