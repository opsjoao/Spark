<?php
while (ob_get_level()) ob_end_clean();
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) session_start();

function sendJson($success, $message, $data = []) {
    while (ob_get_level()) ob_end_clean();

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data), JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    ob_start();
    require_once('../../../formulario-cadastro-login/verificacao.php');
    ob_end_clean();

    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception("Usuário não está autenticado.");
    }

    $idUsuario = $_SESSION['id_usuario'];

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método inválido.");
    }

    $conn = new mysqli("localhost", "root", "", "Spark");
    if ($conn->connect_error) throw new Exception("Falha na conexão.");

    mysqli_set_charset($conn, "utf8mb4");

    // ===========================
    // CAPTURA DOS DADOS DO FORM
    // ===========================
    $titulo      = trim($_POST['event_title'] ?? '');
    $descricao   = trim($_POST['event_description'] ?? '');
    $datetime    = trim($_POST['event_date'] ?? '');
    $endereco    = trim($_POST['event_location'] ?? '');
    $nomeParque  = trim($_POST['park_name'] ?? '');
    $cep         = trim($_POST['park_cep'] ?? ''); // <-- ADICIONADO

    if (!$titulo || !$descricao || !$datetime || !$nomeParque) {
        throw new Exception("Preencha todos os campos obrigatórios.");
    }

    // ===========================
    // FORMATAÇÃO DE DATAS
    // ===========================
    $data = date('Y-m-d', strtotime($datetime));
    $horaInicio = date('H:i:s', strtotime($datetime));
    $horaTermino = date('H:i:s', strtotime("+2 hours", strtotime($datetime)));

    // ===========================
    // BUSCAR PARQUE
    // ===========================
    $stmt = $conn->prepare("SELECT idParque FROM Parque WHERE nome = ?");
    $stmt->bind_param("s", $nomeParque);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {

        $idParque = $res->fetch_assoc()['idParque'];

    } else {

        $stmt->close();

        // ===========================
        // CRIAR PARQUE COM CEP
        // ===========================
        $stmt = $conn->prepare("
        INSERT INTO Parque (nome, endereco, cep)
        VALUES (?, ?, ?)
        ");

        $stmt->bind_param("sss", $nomeParque, $endereco, $cep);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao criar o parque.");
        }

        $idParque = $conn->insert_id;
    }

    $stmt->close();

    // ===========================
    // CRIAR EVENTO
    // ===========================
    $stmt = $conn->prepare("
    INSERT INTO Evento
    (idUsuario, idParque, nome, descricao, dia, horario_inicio, horario_termino)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("iisssss",
                      $idUsuario, $idParque, $titulo, $descricao,
                      $data, $horaInicio, $horaTermino
    );

    if (!$stmt->execute()) {
        throw new Exception("Erro ao salvar o evento no banco.");
    }

    $idEvento = $conn->insert_id;
    $stmt->close();

    // ===========================
    // CRIADOR VIRA PARTICIPANTE
    // ===========================
    $stmt = $conn->prepare("
    INSERT INTO Participantes (idEvento, idUsuario, status, dataInscricao)
    VALUES (?, ?, 'inscrito', NOW())
    ON DUPLICATE KEY UPDATE status='inscrito'
    ");


    $stmt->bind_param("ii", $idEvento, $idUsuario);
    $stmt->execute();
    $stmt->close();

    $conn->close();

    sendJson(true, "Evento criado com sucesso!", [
        "idEvento" => $idEvento
    ]);

} catch (Exception $e) {
    error_log("ERRO: " . $e->getMessage());
    sendJson(false, $e->getMessage());
}
?>
