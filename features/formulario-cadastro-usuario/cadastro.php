<?php
// Inclui o arquivo de conexão
require_once '../conexao.php';

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Filtra e recupera os dados do formulário
    $nome = $conn->real_escape_string($_POST['name']);
    $username = $conn->real_escape_string($_POST['username']); // NOVO CAMPO
    $cpf = $conn->real_escape_string($_POST['cpf']) ?: null;
    $data_nasc = $conn->real_escape_string($_POST['birthdate']);
    $email = $conn->real_escape_string($_POST['email']);
    $senha = $_POST['password'];
    $confirm_senha = $_POST['confirm_password'];
    $genero = $_POST['gender'] ?? 'Não informado';
    $tipo = "comum";

    // --- CORREÇÃO PRINCIPAL APLICADA AQUI ---
    // Pega o CPF com máscara do formulário e remove todos os caracteres não numéricos
    $cpf_com_mascara = $_POST['cpf'] ?? '';
    $cpf = !empty($cpf_com_mascara) ? preg_replace('/[^0-9]/', '', $cpf_com_mascara) : null;

    // --- VALIDAÇÕES PRIMÁRIAS ---
    if (preg_match('/\s/', $username)) {
        die("Erro: O nome de usuário não pode conter espaços. <a href='javascript:history.back()'>Tente novamente</a>.");
    }
    if (strlen($username) > 30) {
        die("Erro: O nome de usuário não pode ter mais de 30 caracteres. <a href='javascript:history.back()'>Tente novamente</a>.");
    }
    if ($senha !== $confirm_senha) {
        die("Erro: As senhas não coincidem. <a href='javascript:history.back()'>Tente novamente</a>.");
    }
    if (strlen($senha) < 6) {
        die("Erro: A senha deve ter pelo menos 6 caracteres. <a href='javascript:history.back()'>Tente novamente</a>.");
    }

    // --- VERIFICAÇÃO DE DADOS DUPLICADOS ---
    // Faz uma única consulta para verificar username, email e CPF
    $stmt_check = $conn->prepare("SELECT username, email, cpf FROM Usuario WHERE username = ? OR email = ? OR (cpf IS NOT NULL AND cpf = ?)");
    $stmt_check->bind_param("sss", $username, $email, $cpf);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        $existente = $result_check->fetch_assoc();
        if ($existente['username'] === $username) {
            die("Erro: Este nome de usuário já está em uso. <a href='javascript:history.back()'>Voltar</a>");
        }
        if ($existente['email'] === $email) {
            die("Erro: Este e-mail já está cadastrado. <a href='javascript:history.back()'>Voltar</a>");
        }
        if ($cpf && $existente['cpf'] === $cpf) {
            die("Erro: Este CPF já está cadastrado. <a href='javascript:history.back()'>Voltar</a>");
        }
    }
    $stmt_check->close();
    
    // Se passou por todas as verificações, prossegue com a inserção
    $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);

    $sql = "INSERT INTO Usuario (nome, username, tipo, email, senha, cpf, genero, data_nasc) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    // bind_param atualizado para 8 strings (ssssssss)
    $stmt->bind_param("ssssssss", $nome, $username, $tipo, $email, $senha_hashed, $cpf, $genero, $data_nasc);

    if ($stmt->execute()) {
        // Redireciona para a tela de login após o sucesso
        header('Location: ../formulario-login/login.php?cadastro=sucesso');
        exit();
    } else {
        echo "Erro ao cadastrar: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>