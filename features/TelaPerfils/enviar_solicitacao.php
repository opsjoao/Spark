<?php
require_once('../formulario-cadastro-login/verificacao.php');
header('Content-Type: application/json');

$idUsuarioLogado = $_SESSION['id_usuario'];
$idAmigoParaAdd = json_decode(file_get_contents('php://input'))->idAmigo;

$servidor = "localhost"; $usuario_db = "root"; $senha_db = ""; $banco = "spark";
$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);

$stmt = $conexao->prepare("INSERT INTO Amizades (idUsuarioSolicitante, idUsuarioRecebedor, status) VALUES (?, ?, 'pendente')");
$stmt->bind_param("ii", $idUsuarioLogado, $idAmigoParaAdd);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Solicitação enviada!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar solicitação.']);
}
$stmt->close();
$conexao->close();
?>