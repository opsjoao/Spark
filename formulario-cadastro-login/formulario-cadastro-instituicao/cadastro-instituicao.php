<?php
// Inclui o arquivo de conexão
require_once '../conexao.php';

// Verifica se o formulário foi submetido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera os dados do formulário
    $nome_instituicao = $conn->real_escape_string($_POST['nome_instituicao']);
    $username = $conn->real_escape_string($_POST['username']);
    $cnpj = $conn->real_escape_string($_POST['cnpj']);
    $email = $conn->real_escape_string($_POST['email']);
    $senha = $_POST['password'];
    $confirm_senha = $_POST['confirm_password'];

    $cnpj_raw = $_POST['cnpj'] ?? '';
    $cnpj = !empty($cnpj_raw) ? $conn->real_escape_string($cnpj_raw) : NULL;
    
    // Valida se as senhas coincidem
    if ($senha !== $confirm_senha) {
        die("As senhas não coincidem.");
    }
    
    // Hash da senha por segurança
    $senha_hashed = password_hash($senha, PASSWORD_DEFAULT);
    
    // Inicia a transação
    $conn->begin_transaction();

    try {
        // 1. INSERE NA TABELA USUARIO PRIMEIRO
        // Note que a data de nascimento é obrigatória, então colocamos uma data padrão.
        // O ideal seria remover NOT NULL do banco ou adicionar o campo no formulário se for relevante.
        $stmt_usuario = $conn->prepare(
            "INSERT INTO Usuario (nome, username, tipo, email, senha, data_nasc) VALUES (?, ?, 'instituicao', ?, ?, '1900-01-01')"
        );
        $stmt_usuario->bind_param("ssss", $nome_instituicao, $username, $email, $senha_hashed);
        $stmt_usuario->execute();

        // Pega o ID do usuário que acabamos de criar
        $idUsuarioNovo = $conn->insert_id;
        if ($idUsuarioNovo == 0) {
            throw new Exception("Não foi possível criar a conta de usuário base.");
        }
        $stmt_usuario->close();

        // 2. INSERE NA TABELA INSTITUICAO, LIGANDO COM O ID DO USUÁRIO
        $stmt_inst = $conn->prepare(
            "INSERT INTO Instituicao (idUsuario, nome, senha, email, cnpj, tipo) VALUES (?, ?, ?, ?, ?, 'institucional')"
        );
        // A senha na tabela Instituicao parece redundante, mas seguimos o seu banco.
        $stmt_inst->bind_param("issss", $idUsuarioNovo, $nome_instituicao, $senha_hashed, $email, $cnpj);
        $stmt_inst->execute();
        $stmt_inst->close();

        // Se tudo deu certo, confirma as alterações
        $conn->commit();
        
        // Redireciona para o login
        header('Location: ../formulario-login/login.php');
        exit();

    } catch (mysqli_sql_exception $exception) {
        // Se algo deu errado, desfaz tudo
        $conn->rollback();
        
        if ($conn->errno == 1062) {
            die("Erro: E-mail, CNPJ ou Nome de Usuário já cadastrado. <a href='javascript:history.back()'>Tente novamente</a>.");
        } else {
            die("Erro ao cadastrar a instituição: " . $exception->getMessage());
        }
    }
}

$conn->close();
?>
