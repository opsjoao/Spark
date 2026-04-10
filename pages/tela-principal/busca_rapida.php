<?php
// Desativa a exibição de erros na tela para não quebrar o JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "Spark";

// Tenta conectar
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

// Se der erro, retorna array vazio em vez de texto de erro
if ($conexao->connect_error) {
    echo json_encode([]);
    exit;
}

$conexao->set_charset("utf8mb4"); // Garante acentos corretos

$termo = "";
if (isset($_GET['termo'])) {
    $termo = $conexao->real_escape_string($_GET['termo']);
}

$resultados = [];

if (!empty($termo)) {
    // MUDANÇA AQUI: Usamos LOWER() para ignorar maiúsculas/minúsculas
    $sql = "
        SELECT idEvento, nome 
        FROM Evento 
        WHERE (LOWER(nome) LIKE LOWER('%$termo%')) 
        AND TIMESTAMP(dia, horario_termino) >= NOW()
        LIMIT 5
    ";

    $query = $conexao->query($sql);

    if ($query && $query->num_rows > 0) {
        while ($row = $query->fetch_assoc()) {
            $resultados[] = $row;
        }
    }
}

echo json_encode($resultados);

$conexao->close();
?>