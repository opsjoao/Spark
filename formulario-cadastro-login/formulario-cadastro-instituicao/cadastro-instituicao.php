<?php
// Inclui o arquivo de conexão
require_once '../conexao.php';

// Verifica se o formulário foi submetido via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Filtra e recupera os dados do formulário com os novos nomes
    $nome_instituicao = $conn->real_escape_string($_POST['nome_instituicao']);
    $cnpj = $conn->real_escape_string($_POST['cnpj']);
    $email = $conn->real_escape_string($_POST['email']);
    $tipo_instituicao = $conn->real_escape_string($_POST['tipo_instituicao']);
    $senha = $_POST['password'];
    $confirm_senha = $_POST['confirm_password'];

    // Valida se as senhas coincidem
    if ($senha !== $confirm_senha) {
        die("As senhas não coincidem.");
    }

    // Hash da senha para segurança
    $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);

    // Usa o CNPJ como um valor temporário para o CPF, para evitar a violação da restrição UNIQUE
    // Esta é uma solução temporária para a apresentação.
    $cpf_placeholder = $cnpj;

    // 1. Inserir um novo registro na tabela Usuario
    $tipo_usuario = "Institucional";
    $data_placeholder = date("Y-m-d"); // Pega a data atual no formato YYYY-MM-DD
$sql_usuario = "INSERT INTO Usuario (nome, tipo, email, senha, cpf, genero, data_nasc) VALUES (?, ?, ?, ?, ?, NULL, ?)";

    $stmt_usuario = $conn->prepare($sql_usuario);
    $stmt_usuario->bind_param("ssssss", $nome_instituicao, $tipo_usuario, $email, $senha_hashed, $cpf_placeholder, $data_placeholder);

    if ($stmt_usuario->execute()) {
        // Pega o ID do usuário recém-criado
        $idUsuario = $conn->insert_id;

        // 2. Agora insere na tabela Instituicao
        $sql_instituicao = "INSERT INTO Instituicao (idUsuario, nome, senha, email, cnpj, tipo) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_instituicao = $conn->prepare($sql_instituicao);
        $stmt_instituicao->bind_param("isssss", $idUsuario, $nome_instituicao, $senha_hashed, $email, $cnpj, $tipo_instituicao);
        
        // Execução
        if ($stmt_instituicao->execute()) {
            // Redireciona para a página de login
            header('Location: ../formulario-login/login.html');
            exit();
        } else {
            echo "Erro ao cadastrar instituição: " . $stmt_instituicao->error;
        }
        $stmt_instituicao->close();
    } else {
        echo "Erro ao cadastrar usuário: " . $stmt_usuario->error;
    }

    $stmt_usuario->close();
}

$conn->close();
?>