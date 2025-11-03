<?php
require_once('../formulario-cadastro-login/verificacao.php');
header('Content-Type: application/json');

$servidor = "localhost"; $usuario_db = "root"; $senha_db = ""; $banco = "spark";
$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) { die(json_encode(['erro' => 'Conexão falhou.'])); }

$termoBusca = $_GET['q'] ?? '';
$idUsuarioLogado = $_SESSION['id_usuario'];

if (strlen($termoBusca) < 2) { die(json_encode([])); }

// Busca usuários que não sejam o próprio usuário logado
$stmt = $conexao->prepare("SELECT idUsuario, nome, username, avatar_path FROM Usuario WHERE username LIKE ? AND idUsuario != ?");
$param = "%" . $termoBusca . "%";
$stmt->bind_param("si", $param, $idUsuarioLogado);
$stmt->execute();
$resultado = $stmt->get_result();
$usuarios = $resultado->fetch_all(MYSQLI_ASSOC);

echo json_encode($usuarios);
$stmt->close();
$conexao->close();
?>