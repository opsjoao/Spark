<?php
require_once '../conexao.php';
header('Content-Type: application/json');

$response = ['disponivel' => false, 'mensagem' => 'E-mail não fornecido.'];
if (isset($_GET['email'])) {
    $email = $_GET['email'];
    $stmt = $conn->prepare("SELECT idUsuario FROM Usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $response = ['disponivel' => false, 'mensagem' => 'Este e-mail já está em uso.'];
    } else {
        $response = ['disponivel' => true];
    }
    $stmt->close();
}
$conn->close();
echo json_encode($response);
?>