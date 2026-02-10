<?php
session_start();
header('Content-Type: application/json'); // Define o tipo de resposta como JSON

if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado.']);
    exit();
}
if (!isset($_POST['idEvento'])) {
    echo json_encode(['success' => false, 'message' => 'ID do evento não fornecido.']);
    exit();
}

$idUsuario = $_SESSION['id_usuario'];
$idEvento = $_POST['idEvento'];

$servidor = "localhost"; $usuario_db = "root"; $senha_db = ""; $banco = "Spark";
$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Falha na conexão com o banco.']);
    exit();
}

// Atualiza o status do participante para 'ativo'
$stmt = $conexao->prepare("UPDATE Participantes SET status = 'ativo' WHERE idUsuario = ? AND idEvento = ? AND status = 'inscrito'");
$stmt->bind_param("ii", $idUsuario, $idEvento);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Evento iniciado com sucesso!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Não foi possível iniciar o evento.']);
}

$stmt->close();
$conexao->close();

?>
