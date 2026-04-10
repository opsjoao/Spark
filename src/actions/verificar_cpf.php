<?php
require_once '../conexao.php';
header('Content-Type: application/json');

$response = ['disponivel' => false, 'mensagem' => 'CPF não fornecido.'];
if (isset($_GET['cpf'])) {
    $cpf = preg_replace('/[^0-9]/', '', $_GET['cpf']);
    if (strlen($cpf) === 11) {
        $stmt = $conn->prepare("SELECT idUsuario FROM Usuario WHERE cpf = ?");
        $stmt->bind_param("s", $cpf);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $response = ['disponivel' => false, 'mensagem' => 'Este CPF já está em uso.'];
        } else {
            $response = ['disponivel' => true];
        }
        $stmt->close();
    }
}
$conn->close();
echo json_encode($response);
?>