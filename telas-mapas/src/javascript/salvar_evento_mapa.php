<?php
while (ob_get_level()) ob_end_clean();
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) session_start();

function sendJson($success, $message, $data = []) {
    // Garante que todo o buffer de saída esteja limpo antes de enviar o JSON
    while (ob_get_level()) ob_end_clean();

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message
    ], $data), JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    // Inclui e limpa o buffer gerado pelo arquivo de verificação
    ob_start();
    require_once('../../../formulario-cadastro-login/verificacao.php');
    ob_end_clean();

    if (!isset($_SESSION['id_usuario'])) {
        throw new Exception("Usuário não está autenticado. Redirecionamento necessário.");
    }

    $idUsuario = $_SESSION['id_usuario'];

    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        throw new Exception("Método de requisição inválido.");
    }

    // ===========================
    // CONEXÃO COM O BANCO DE DADOS
    // ===========================
    $conn = new mysqli("localhost", "root", "", "Spark");
    if ($conn->connect_error) throw new Exception("Falha na conexão com o banco de dados.");

    mysqli_set_charset($conn, "utf8mb4");

    // ===========================
    // CAPTURA DOS DADOS DO FORM
    // ===========================
    $titulo       = trim($_POST['event_title'] ?? '');
    $descricao    = trim($_POST['event_description'] ?? '');
    $datetime     = trim($_POST['event_date'] ?? '');
    $endereco     = trim($_POST['event_location'] ?? '');
    $nomeParque   = trim($_POST['park_name'] ?? '');
    $cep          = trim($_POST['park_cep'] ?? '');
    // NOVO CAMPO: Adiciona o place_id se ele for enviado do frontend
    $placeId      = trim($_POST['park_place_id'] ?? '');

    if (!$titulo || !$descricao || !$datetime || !$nomeParque) {
        throw new Exception("Preencha todos os campos obrigatórios: Título, Descrição, Data/Hora e Nome do Parque.");
    }

    // ===========================
    // FORMATAÇÃO DE DATAS
    // ===========================
    $data = date('Y-m-d', strtotime($datetime));
    $horaInicio = date('H:i:s', strtotime($datetime));
    $horaTermino = date('H:i:s', strtotime("+2 hours", strtotime($datetime))); // +2 horas como padrão

    // ===========================
    // BUSCAR OU CRIAR PARQUE
    // ===========================
    $idParque = null;

    // 1. Tentar encontrar por Place ID (mais confiável)
    if ($placeId) {
        $stmt = $conn->prepare("SELECT idParque FROM Parque WHERE place_id = ?");
        $stmt->bind_param("s", $placeId);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $idParque = $res->fetch_assoc()['idParque'];
        }
        $stmt->close();
    }

    // 2. Se não encontrou por Place ID, tentar por Nome (fallback)
    if (!$idParque) {
        $stmt = $conn->prepare("SELECT idParque FROM Parque WHERE nome = ?");
        $stmt->bind_param("s", $nomeParque);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $idParque = $res->fetch_assoc()['idParque'];
        }
        $stmt->close();
    }

    // 3. Se ainda não existe, CRIAR PARQUE
    if (!$idParque) {

        // Nota: Certifique-se de que sua tabela `Parque` no banco de dados tenha as colunas `nome`, `endereco`, `cep` e `place_id`
        $stmt = $conn->prepare("
        INSERT INTO Parque (nome, endereco, cep, place_id)
        VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param("ssss", $nomeParque, $endereco, $cep, $placeId);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao criar o parque no banco de dados: " . $stmt->error);
        }

        $idParque = $conn->insert_id;
        $stmt->close();
    }
    // Obs: Se o parque foi encontrado por nome, mas o place_id não estava salvo (idParque existe, mas placeId estava vazio no banco),
    // é recomendado atualizar o place_id. (Opcional, mas boa prática):
    elseif ($idParque && $placeId) {
         // Verifica se o place_id já foi salvo para evitar UPDATE desnecessário
         $check_stmt = $conn->prepare("SELECT place_id FROM Parque WHERE idParque = ?");
         $check_stmt->bind_param("i", $idParque);
         $check_stmt->execute();
         $result = $check_stmt->get_result();
         $db_place_id = $result->fetch_assoc()['place_id'] ?? null;
         $check_stmt->close();

         if (empty($db_place_id)) {
            $update_stmt = $conn->prepare("UPDATE Parque SET place_id = ? WHERE idParque = ?");
            $update_stmt->bind_param("si", $placeId, $idParque);
            $update_stmt->execute();
            $update_stmt->close();
         }
    }


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
        throw new Exception("Erro ao salvar o evento no banco de dados: " . $stmt->error);
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

    sendJson(true, "Evento criado com sucesso! ID: {$idEvento}", [
        "idEvento" => $idEvento
    ]);

} catch (Exception $e) {
    // Log do erro completo no servidor para debugging
    error_log("ERRO ao salvar evento (Usuário ID: " . ($idUsuario ?? 'N/A') . "): " . $e->getMessage());
    sendJson(false, "Falha ao criar o evento. " . $e->getMessage());
}
?>
