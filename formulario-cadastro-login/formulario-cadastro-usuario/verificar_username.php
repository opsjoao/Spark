<?php
// Define que a resposta será em formato JSON
header('Content-Type: application/json');

// Inclui o arquivo de conexão
require_once '../conexao.php';

// Verifica se o username foi enviado via GET
if (isset($_GET['username'])) {
    $username = $_GET['username'];

    // Prepara a consulta para contar quantos usuários têm esse username
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM Usuario WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    // Se a contagem for maior que 0, o username já existe
    if ($data['total'] > 0) {
        echo json_encode(['disponivel' => false, 'mensagem' => 'Este nome de usuário já está em uso.']);
    } else {
        echo json_encode(['disponivel' => true, 'mensagem' => 'Nome de usuário disponível!']);
    }

    $stmt->close();
} else {
    // Se nenhum username for fornecido, retorna um erro
    echo json_encode(['disponivel' => false, 'mensagem' => 'Nenhum nome de usuário fornecido.']);
}

$conn->close();
?>