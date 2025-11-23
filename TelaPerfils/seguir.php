<?php
session_start();
header('Content-Type: application/json');

// --- CONFIGURAÇÃO DO BANCO DE DADOS ---
$host = 'localhost'; $db = 'Spark'; $user = 'root'; $pass = '';
$conn = new mysqli($host, $user, $pass, $db);

// VERIFICAÇÃO CORRIGIDA
if (!isset($_SESSION['id_usuario']) || !isset($_POST['id'])) {
    echo json_encode(['success' => false, 'msg' => 'Dados inválidos']);
    exit;
}

$eu = $_SESSION['id_usuario']; // <--- CORRIGIDO
$ele = intval($_POST['id']);

// TRAVA DE AUTO-SEGUIMENTO
if ($eu === $ele) {
   echo json_encode(['success' => false, 'msg' => 'Não pode seguir a si mesmo']);
   exit;
}

// Verifica se já segue
$check = $conn->prepare("SELECT id FROM Seguidores WHERE idSeguidor = ? AND idSeguido = ?");
$check->bind_param("ii", $eu, $ele);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // UNFOLLOW
    $stmt = $conn->prepare("DELETE FROM Seguidores WHERE idSeguidor = ? AND idSeguido = ?");
    $stmt->bind_param("ii", $eu, $ele);
    $stmt->execute();
    $acao = 'deixou_de_seguir';
} else {
    // FOLLOW
    $stmt = $conn->prepare("INSERT INTO Seguidores (idSeguidor, idSeguido) VALUES (?, ?)");
    $stmt->bind_param("ii", $eu, $ele);
    $stmt->execute();
    $acao = 'seguiu';
}

echo json_encode(['success' => true, 'acao' => $acao]);
$conn->close();
?>