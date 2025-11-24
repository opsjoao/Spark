<?php
session_start();
header('Content-Type: application/json');

// Verifica login
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'msg' => 'Usuário não logado.']);
    exit;
}

if (!isset($_POST['id_avaliacao'])) {
    echo json_encode(['success' => false, 'msg' => 'ID da avaliação não fornecido.']);
    exit;
}

$idUsuario = $_SESSION['id_usuario'];
$idAvaliacao = intval($_POST['id_avaliacao']);

// Conexão
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "Spark";

$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) {
    echo json_encode(['success' => false, 'msg' => 'Erro de conexão.']);
    exit;
}

// 1. Verifica se a avaliação pertence mesmo ao usuário logado (Segurança)
$stmtCheck = $conexao->prepare("SELECT idAvaliacao FROM Avaliacao_evento WHERE idAvaliacao = ? AND idUsuario = ?");
$stmtCheck->bind_param("ii", $idAvaliacao, $idUsuario);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if ($resCheck->num_rows === 0) {
    echo json_encode(['success' => false, 'msg' => 'Avaliação não encontrada ou você não tem permissão para excluí-la.']);
    exit;
}
$stmtCheck->close();

// 2. Exclui a avaliação
$stmtDelete = $conexao->prepare("DELETE FROM Avaliacao_evento WHERE idAvaliacao = ?");
$stmtDelete->bind_param("i", $idAvaliacao);

if ($stmtDelete->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'msg' => 'Erro ao excluir no banco de dados.']);
}

$stmtDelete->close();
$conexao->close();
?>