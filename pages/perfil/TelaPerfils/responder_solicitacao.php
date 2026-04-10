<?php
require_once('../formulario-cadastro-login/verificacao.php');
header('Content-Type: application/json');

$idUsuarioLogado = $_SESSION['id_usuario'];
$input = json_decode(file_get_contents('php://input'));
$idSolicitante = $input->idSolicitante;
$acao = $input->acao; // 'aceitar' ou 'recusar'

$servidor = "localhost"; $usuario_db = "root"; $senha_db = ""; $banco = "spark";
$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);

if ($acao == 'aceitar') {
    $stmt = $conexao->prepare("UPDATE Amizades SET status = 'aceita' WHERE idUsuarioSolicitante = ? AND idUsuarioRecebedor = ?");
} else {
    $stmt = $conexao->prepare("DELETE FROM Amizades WHERE idUsuarioSolicitante = ? AND idUsuarioRecebedor = ?");
}
$stmt->bind_param("ii", $idSolicitante, $idUsuarioLogado);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
$stmt->close();
$conexao->close();
?>