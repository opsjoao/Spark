<?php
header('Content-Type: application/json');
require_once '../conexao.php';

$response = ['disponivel' => false, 'mensagem' => 'CPF não fornecido.'];

if (isset($_GET['cpf'])) {
    // CORREÇÃO APLICADA AQUI: Remove todos os caracteres que não são dígitos
    $cpf = preg_replace('/[^0-9]/', '', $_GET['cpf']);

    if (strlen($cpf) === 11) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM Usuario WHERE cpf = ?");
        $stmt->bind_param("s", $cpf);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        if ($data['total'] > 0) {
            $response = ['disponivel' => false, 'mensagem' => 'Este CPF já está em uso.'];
        } else {
            $response = ['disponivel' => true, 'mensagem' => 'CPF disponível!'];
        }
        $stmt->close();
    }
}

$conn->close();
echo json_encode($response);
?>