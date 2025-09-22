<?php
// Inclua o arquivo de conexão com o banco de dados
// A variável $conn será a sua conexão.
require_once '../conexao.php'; // Substitua 'conexao.php' pelo nome do seu arquivo de conexão

// Inicia a sessão para armazenar informações
session_start();

// Verifica se o formulário foi enviado usando o método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Pega o e-mail do formulário e limpa de possíveis ataques (como injeção de SQL)
    $email = $_POST['email'];

    // 2. Prepara a consulta SQL para evitar injeção de SQL
    // A consulta busca o e-mail na tabela 'usuarios' (ajuste o nome da sua tabela)
    $sql = "SELECT id FROM usuarios WHERE email = ?";
    
    // 3. Prepara e executa a instrução
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email); // 's' indica que o parâmetro é uma string
    $stmt->execute();
    
    // 4. Armazena o resultado da consulta
    $result = $stmt->get_result();

    // 5. Verifica se encontrou algum e-mail
    if ($result->num_rows > 0) {
        // O e-mail foi encontrado no banco de dados
        // Você pode redirecionar para uma página de sucesso
        // ou enviar um e-mail com o link de redefinição de senha.
        
        // Exemplo: Armazena o e-mail na sessão e redireciona para a página de redefinição
        $_SESSION['email_recuperacao'] = $email;
        header("Location: redefinir-senha.php"); // Altere para o nome do seu arquivo
        exit();
    } else {
        // O e-mail NÃO foi encontrado
        
        // Exemplo: Redireciona de volta para o formulário com uma mensagem de erro
        $_SESSION['mensagem_erro'] = "O e-mail informado não está cadastrado em nosso sistema.";
        header("Location: recuperar-senha.php"); // Altere para o nome do seu formulário
        exit();
    }

    // Fecha a instrução
    $stmt->close();
}

// Redireciona caso o acesso não tenha vindo do formulário POST
header("Location: recuperar-senha.php");
exit();
?>