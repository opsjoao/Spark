<?php
// Define que a resposta será em formato JSON
header('Content-Type: application/json');

// Inclui o arquivo de conexão
require_once '../conexao.php';

// Resposta padrão em caso de erro
$response = ['disponivel' => false, 'mensagem' => 'E-mail inválido.'];

// Verifica se o e-mail foi enviado e se é um formato de e-mail válido
if (isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    $email = $_GET['email'];

    // Prepara a consulta para contar quantos usuários têm esse e-mail
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM Usuario WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    // Se a contagem for maior que 0, o e-mail já existe
    if ($data['total'] > 0) {
        $response = ['disponivel' => false, 'mensagem' => 'Este e-mail já está em uso.'];
    } else {
        $response = ['disponivel' => true, 'mensagem' => 'E-mail disponível!'];
    }

    $stmt->close();
}

$conn->close();

// Retorna a resposta em formato JSON
echo json_encode($response);
?>