<?php
// 1. Configurações e Conexão com o Banco
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "Spark"; // Verifique se o nome do banco está correto

$conn = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// 2. Receber os dados do formulário via POST
$nome = $_POST['name'];
$username = $_POST['username']; // Novo campo
$cpf = $_POST['cpf'] ?: null; // Define como nulo se for enviado vazio
$data_nasc = $_POST['birthdate'];
$email = $_POST['email'];
$senha = $_POST['password'];
$senha_confirm = $_POST['confirm_password'];
$genero = $_POST['gender'];

// 3. Validações
// a) Verificar se as senhas coincidem
if ($senha !== $senha_confirm) {
    die("Erro: As senhas não coincidem. <a href='javascript:history.back()'>Tente novamente</a>.");
}

// b) Verificar se email, username ou CPF já existem (usando prepared statements)
$stmt = $conn->prepare("SELECT email FROM Usuario WHERE email = ? OR username = ? OR cpf = ?");
$stmt->bind_param("sss", $email, $username, $cpf);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    die("Erro: E-mail, username ou CPF já cadastrado. <a href='javascript:history.back()'>Tente novamente</a>.");
}
$stmt->close();

// 4. Criptografar a senha (Segurança Essencial!)
$senha_hash = password_hash($senha, PASSWORD_DEFAULT);

// 5. Inserir o novo usuário no banco de dados
$stmt = $conn->prepare("
    INSERT INTO Usuario (nome, username, tipo, email, senha, cpf, genero, data_nasc) 
    VALUES (?, ?, 'comum', ?, ?, ?, ?, ?)
");

// s = string, s = string, s = string, ...
$stmt->bind_param("sssssss", $nome, $username, $email, $senha_hash, $cpf, $genero, $data_nasc);

if ($stmt->execute()) {
    // Se o cadastro for bem-sucedido, exibe uma mensagem e um link para o login
    echo "
        <div style='font-family: sans-serif; text-align: center; padding-top: 50px;'>
            <h1>Usuário cadastrado com sucesso!</h1>
            <p>Agora você já pode fazer o login.</p>
            <a href='../formulario-login/login.php' style='display: inline-block; padding: 10px 20px; background-color: #448019; color: white; text-decoration: none; border-radius: 5px;'>Ir para Login</a>
        </div>
    ";
} else {
    echo "Erro ao cadastrar usuário: " . $stmt->error;
}

// 6. Fechar as conexões
$stmt->close();
$conn->close();

?>