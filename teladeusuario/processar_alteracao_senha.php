<?php
// Inicia a sessão...
session_start();

// Verifica se o usuário está logado...
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../formulario-cadastro-login/formulario-login.php');
    exit();
}

// Verifica se o método de requisição é POST...
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: alterar_senha.php');
    exit();
}

// =======================================================================
//  INCLUI O ARQUIVO DE CONEXÃO COM O BANCO DE DADOS (CORREÇÃO)
//  Esta linha estava faltando. Ela define a variável $conexao.
// =======================================================================
require_once('../conexao.php');

// Pega os dados do formulário... (CORREÇÃO)
$idUsuarioLogado = $_SESSION['id_usuario'];
$senha_atual = $_POST['senha_atual'];
$nova_senha = $_POST['nova_senha'];
$confirmar_senha = $_POST['confirmar_senha'];

// 1. Verificar se a nova senha e a confirmação são iguais.
if ($nova_senha !== $confirmar_senha) {
    $_SESSION['error_message'] = "A nova senha e a confirmação não correspondem.";
    header('Location: alterar_senha.php');
    exit();
}

// 2. Buscar a senha atual (hash) do usuário no banco de dados.
// Esta parte do seu código já estava correta.
$stmt = $conn->prepare("SELECT senha FROM Usuario WHERE idUsuario = ?");
$stmt->bind_param("i", $idUsuarioLogado);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$stmt->close();

if (!$usuario) {
    $_SESSION['error_message'] = "Usuário não encontrado.";
    header('Location: alterar_senha.php');
    $conn->close();
    exit();
}

// 3. Verificar se a senha atual fornecida corresponde à senha no banco.
if (!password_verify($senha_atual, $usuario['senha'])) {
    $_SESSION['error_message'] = "A senha atual está incorreta.";
    header('Location: alterar_senha.php');
    $conn->close();
    exit();
}

// 4. Se a senha atual estiver correta, crie um novo hash para a nova senha.
$hash_nova_senha = password_hash($nova_senha, PASSWORD_DEFAULT);

// 5. Atualizar a senha no banco de dados.
$stmt = $conn->prepare("UPDATE Usuario SET senha = ? WHERE idUsuario = ?");
$stmt->bind_param("si", $hash_nova_senha, $idUsuarioLogado);

if ($stmt->execute()) {
    $_SESSION['message'] = "Senha alterada com sucesso!";
    header('Location: teladeusuario.php'); // Redireciona para a tela de perfil
} else {
    $_SESSION['error_message'] = "Erro ao atualizar a senha: " . $conn->error;
    header('Location: alterar_senha.php');
}

$stmt->close();
$conn->close();
exit();
?>