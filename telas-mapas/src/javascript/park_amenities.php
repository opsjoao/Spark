<?php
// =========================================================================
// park_amenities.php - Lógica de Votação e Consulta de Amenidades por Place ID
// =========================================================================

// Configurações e Inicialização
while (ob_get_level()) ob_end_clean();
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Função utilitária para enviar respostas JSON e encerrar o script.
 */
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
    // ------------------------------------
    // AUTENTICAÇÃO E CONEXÃO
    // ------------------------------------

    // Simulação da verificação de login
    // Ajuste o caminho conforme a sua estrutura
    /*
    ob_start();
    require_once('../../../formulario-cadastro-login/verificacao.php');
    ob_end_clean();
    */

    if (!isset($_SESSION['id_usuario'])) {
        // Para a lógica de amenidades, permitir consulta GET sem login, mas exigir para votar (POST)
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            throw new Exception("É necessário estar logado para votar em amenidades.");
        }
        // Se for GET (consulta), continua sem idUsuario.
        $idUsuario = null; 
    } else {
        $idUsuario = $_SESSION['id_usuario'];
    }

    // CONEXÃO COM O BANCO DE DADOS
    $conn = new mysqli("localhost", "root", "", "Spark");
    if ($conn->connect_error) {
        throw new Exception("Falha na conexão com o banco de dados.");
    }
    mysqli_set_charset($conn, "utf8mb4");

    // ------------------------------------
    // LÓGICA DE REQUISIÇÃO (GET ou POST)
    // ------------------------------------

    // ====================================================
    // 1. REQUISIÇÃO GET: Consultar Contagem de Votos
    // ====================================================
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $placeId = trim($_GET['place_id'] ?? '');

        if (empty($placeId)) {
            throw new Exception("Place ID do parque não fornecido para consulta.");
        }
        
        // --- Consulta de Contagem de Votos ---
        // A consulta SQL busca as amenidades e a contagem total de votos para o Place ID
        $query = "
            SELECT 
                a.amenity_name, 
                SUM(pa.votes) as total_votes
            FROM park_amenities pa
            JOIN amenities a ON pa.id_amenity = a.id_amenity
            WHERE pa.place_id = ?
            GROUP BY a.amenity_name
            ORDER BY total_votes DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $placeId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $counts = [];
        while ($row = $result->fetch_assoc()) {
            $counts[$row['amenity_name']] = (int)$row['total_votes'];
        }
        $stmt->close();

        // --- Consulta de Voto do Usuário (opcional, apenas se logado) ---
        $userVotes = [];
        if ($idUsuario) {
            // Assume que você tem uma tabela para rastrear o voto do usuário
            // Exemplo: Tabela `user_amenity_votes` (idUsuario, place_id, id_amenity)
            // A implementação exata depende da sua estrutura de DB.
            // Para simplificar, vou pular esta parte e focar na contagem geral.
            // O frontend pode gerenciar a indicação do voto do usuário.
        }

        sendJson(true, "Contagem de amenidades carregada com sucesso.", [
            "place_id" => $placeId,
            "counts" => $counts
        ]);

    }

    // ====================================================
    // 2. REQUISIÇÃO POST: Votar em uma Amenidade
    // ====================================================
    elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
        
        if (!$idUsuario) {
            throw new Exception("Você deve estar logado para votar.");
        }
        
        $placeId = trim($_POST['park_place_id'] ?? '');
        $amenityName = trim($_POST['amenity_name'] ?? '');
        $action = trim($_POST['action'] ?? ''); // 'upvote' ou 'downvote'
        
        if (empty($placeId) || empty($amenityName) || !in_array($action, ['upvote', 'downvote'])) {
            throw new Exception("Dados insuficientes para registrar o voto.");
        }
        
        // ------------------------------------
        // ENCONTRAR O ID DA AMENIDADE
        // ------------------------------------
        $idAmenity = null;
        $stmt = $conn->prepare("SELECT id_amenity FROM amenities WHERE amenity_name = ?");
        $stmt->bind_param("s", $amenityName);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res->num_rows > 0) {
            $idAmenity = $res->fetch_assoc()['id_amenity'];
        } else {
            // Opcional: Se a amenidade não existir, talvez você queira criá-la aqui
            throw new Exception("Amenidade '{$amenityName}' não reconhecida.");
        }
        $stmt->close();
        
        // ------------------------------------
        // LÓGICA DO VOTO (Upsert)
        // ------------------------------------
        
        // Define o valor do voto (1 para upvote, -1 para downvote)
        $voteValue = ($action === 'upvote') ? 1 : -1;
        
        // Consulta para Inserir/Atualizar o voto na tabela `park_amenities`
        // 
        // 1. Tenta ATUALIZAR (incrementar/decrementar)
        // 2. Se não encontrou, INSERE o novo registro (park_amenities)
        $query = "
            INSERT INTO park_amenities (place_id, id_amenity, votes) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE votes = votes + ?
        ";

        $stmt = $conn->prepare($query);
        // Note que o $voteValue é passado duas vezes para a cláusula ON DUPLICATE KEY UPDATE
        $stmt->bind_param("siii", $placeId, $idAmenity, $voteValue, $voteValue);

        if (!$stmt->execute()) {
            throw new Exception("Erro ao registrar o voto: " . $stmt->error);
        }
        $stmt->close();

        // ------------------------------------
        // RETORNAR NOVA CONTAGEM TOTAL
        // ------------------------------------
        $newCount = 0;
        $stmt = $conn->prepare("SELECT votes FROM park_amenities WHERE place_id = ? AND id_amenity = ?");
        $stmt->bind_param("si", $placeId, $idAmenity);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $newCount = (int)$res->fetch_assoc()['votes'];
        }
        $stmt->close();

        sendJson(true, "Voto registrado com sucesso para '{$amenityName}'.", [
            "amenity_name" => $amenityName,
            "new_count" => $newCount
        ]);

    } else {
        throw new Exception("Método de requisição não suportado.");
    }

} catch (Exception $e) {
    error_log("ERRO em park_amenities.php (Usuário ID: " . ($idUsuario ?? 'N/A') . "): " . $e->getMessage());
    $conn->close();
    sendJson(false, "Falha na operação de amenidades. " . $e->getMessage());
}
?>