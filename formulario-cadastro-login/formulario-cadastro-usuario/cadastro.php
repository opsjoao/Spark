<?php
// Inclui o arquivo de conexão
require_once '../conexao.php';

<?php

// Coloque esta função no início do seu arquivo cadastro.php

function validaCPF($cpf) {
    // Extrai somente os números
    $cpf = preg_replace('/[^0-9]/is', '', $cpf);

    // Verifica se foi informado todos os digitos corretamente
    if (strlen($cpf) != 11) {
        return false;
    }

    // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    // Faz o cálculo para validar o CPF
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    return true;
}

// ... O resto do seu código PHP começa aqui
// Ex: include 'conexao.php';

?>

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

     // 1. Verifica se contém espaços
    if (preg_match('/\s/', $username)) {
        die("Erro: O nome de usuário não pode conter espaços. <a href='javascript:history.back()'>Tente novamente</a>.");
    }

    // 2. Verifica o comprimento (apesar do HTML já limitar, é uma boa segurança)
    if (strlen($username) > 30) {
        die("Erro: O nome de usuário não pode ter mais de 30 caracteres. <a href='javascript:history.back()'>Tente novamente</a>.");
    }

    // --- VERIFICAÇÃO DO CPF ---
    if (validaCPF($cpf)) {
        // CPF é válido, continue com o processo de cadastro
        // Ex: Inserir no banco de dados
        
        echo "<h1>Cadastro realizado com sucesso!</h1>";
        // header('Location: ../pagina-de-sucesso.html');
        // exit();

    } else {
        // CPF é inválido, retorne uma mensagem de erro
        // Você pode redirecionar de volta para o formulário com uma mensagem
        
        echo "<h1>Erro: CPF inválido!</h1>";
        // header('Location: ./cadastro-usuario.html?erro=cpf_invalido');
        // exit();
    }

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
}

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


$conn->close();
?>
