<?php
require_once '../conexao.php';
header('Content-Type: application/json');

$response = ['disponivel' => false, 'mensagem' => 'Username não fornecido.'];
if (isset($_GET['username'])) {
    $username = $_GET['username'];
    $stmt = $conn->prepare("SELECT idUsuario FROM Usuario WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $response = ['disponivel' => false, 'mensagem' => 'Este nome de usuário já está em uso.'];
    } else {
        $response = ['disponivel' => true];
    }
    $stmt->close();
}
$conn->close();
echo json_encode($response);
?>