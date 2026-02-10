<?php
// Define que a resposta será em formato JSON
header('Content-Type: application/json');

// Inclui o arquivo de conexão
require_once '../conexao.php';

// Resposta padrão
$response = ['disponivel' => false, 'mensagem' => 'CPF não fornecido.'];

if (isset($_GET['cpf'])) {
    // Remove todos os caracteres que não são dígitos (pontos, traços, etc.)
    $cpf = preg_replace('/[^0-9]/', '', $_GET['cpf']);

    if (strlen($cpf) === 11) {
        // Prepara a consulta para contar quantos usuários têm esse CPF
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

// Retorna a resposta em formato JSON
echo json_encode($response);
?>