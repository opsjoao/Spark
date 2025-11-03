<?php
header('Content-Type: application/json');
require_once '../conexao.php';

$response = ['disponivel' => false, 'mensagem' => 'CNPJ não fornecido.'];

if (isset($_GET['cnpj'])) {
    // Limpa o CNPJ para conter apenas números
    $cnpj = preg_replace('/[^0-9]/', '', $_GET['cnpj']);

    if (strlen($cnpj) === 14) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM Instituicao WHERE cnpj = ?");
        $stmt->bind_param("s", $cnpj);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();

        if ($data['total'] > 0) {
            $response = ['disponivel' => false, 'mensagem' => 'Este CNPJ já está em uso.'];
        } else {
            $response = ['disponivel' => true, 'mensagem' => '']; // CNPJ disponível, não mostra nada
        }
        $stmt->close();
    }
}

$conn->close();
echo json_encode($response);
?>