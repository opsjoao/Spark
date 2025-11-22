<?php
// Remove TODOS os outputs anteriores
while (ob_get_level()) {
    ob_end_clean();
}
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('America/Sao_Paulo');

// Inicia sessão se ainda não foi iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Função para enviar JSON limpo
function sendJson($success, $message, $data = []) {
    // Limpa TUDO antes de enviar
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');

    $response = array_merge(['success' => $success, 'message' => $message], $data);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
}

error_log("=== INÍCIO salvar_evento_mapa.php ===");

try {
    // Inclui verificacao.php mas captura qualquer output
    ob_start();
    require_once('../../../formulario-cadastro-login/verificacao.php');
    ob_end_clean(); // Descarta qualquer output do verificacao.php

    error_log("✓ verificacao.php incluído");

    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception('Usuário não está logado');
    }

    $idUsuarioLogado = $_SESSION['id_usuario'];
    error_log("✓ ID Usuário: " . $idUsuarioLogado);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método HTTP inválido');
    }

    // Conexão
    $servidor = "localhost";
    $usuario_db = "root";
    $senha_db = "";
    $banco = "Spark";

    $conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);

    if ($conexao->connect_error) {
        throw new Exception('Erro ao conectar ao banco');
    }

    mysqli_set_charset($conexao, "utf8mb4");
    error_log("✓ Conexão OK");

    // Captura e valida dados
    $nomeEvento = trim($_POST['event_title'] ?? '');
    $descricaoEvento = trim($_POST['event_description'] ?? '');
    $dataHoraEvento = trim($_POST['event_date'] ?? '');
    $enderecoParque = trim($_POST['event_location'] ?? '');
    $nomeParque = trim($_POST['park_name'] ?? '');

    if (empty($nomeEvento) || empty($dataHoraEvento) || empty($nomeParque) || empty($descricaoEvento)) {
        throw new Exception('Preencha todos os campos obrigatórios');
    }

    // Formata datas
    $data = date('Y-m-d', strtotime($dataHoraEvento));
    $horarioInicio = date('H:i:s', strtotime($dataHoraEvento));
    $horarioTermino = date('H:i:s', strtotime('+2 hours', strtotime($dataHoraEvento)));

    // Busca ou cria parque
    $stmt = $conexao->prepare("SELECT idParque FROM Parque WHERE nome = ?");
    $stmt->bind_param("s", $nomeParque);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $idParque = $result->fetch_assoc()['idParque'];
        error_log("✓ Parque encontrado: {$idParque}");
    } else {
        $stmt->close();
        $stmt = $conexao->prepare("INSERT INTO Parque (nome, endereco, status) VALUES (?, ?, 'ativo')");
        $stmt->bind_param("ss", $nomeParque, $enderecoParque);

        if (!$stmt->execute()) {
            throw new Exception('Erro ao criar parque');
        }

        $idParque = $conexao->insert_id;
        error_log("✓ Parque criado: {$idParque}");
    }
    $stmt->close();

    // Cria evento
    $stmt = $conexao->prepare("INSERT INTO Evento (idUsuario, idParque, nome, descricao, dia, horario_inicio, horario_termino, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'ativo')");
    $stmt->bind_param("iisssss", $idUsuarioLogado, $idParque, $nomeEvento, $descricaoEvento, $data, $horarioInicio, $horarioTermino);

    if (!$stmt->execute()) {
        throw new Exception('Erro ao criar evento');
    }

    $idEvento = $conexao->insert_id;
    $stmt->close();
    error_log("✓ Evento criado: {$idEvento}");

    // Adiciona participante
    $stmt = $conexao->prepare("INSERT INTO Participantes (idEvento, idUsuario, status) VALUES (?, ?, 'inscrito') ON DUPLICATE KEY UPDATE status='inscrito'");
    $stmt->bind_param("ii", $idEvento, $idUsuarioLogado);
    $stmt->execute();
    $stmt->close();

    $conexao->close();
    error_log("=== SUCESSO ===");

    sendJson(true, 'Evento criado com sucesso!', ['idEvento' => $idEvento]);

} catch (Exception $e) {
    error_log("ERRO: " . $e->getMessage());
    sendJson(false, $e->getMessage());
}
?>
