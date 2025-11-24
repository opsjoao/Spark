<?php
// =========================================================================
// park_reviews.php - Gerenciamento de Avaliações de Parques
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
    // 1. REQUISIÇÃO GET: Buscar Avaliações do Parque
    // ====================================================
    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $placeId = trim($_GET['place_id'] ?? '');

        if (empty($placeId)) {
            throw new Exception("Place ID do parque não fornecido.");
        }
        
        // Primeiro, busca ou cria o parque na tabela Parque
        $stmtParque = $conn->prepare("SELECT idParque FROM Parque WHERE cep = ? OR endereco LIKE ? LIMIT 1");
        $likeAddress = "%{$placeId}%";
        $stmtParque->bind_param("ss", $placeId, $likeAddress);
        $stmtParque->execute();
        $resultParque = $stmtParque->get_result();
        
        if ($resultParque->num_rows === 0) {
            // Parque não existe ainda, retorna array vazio
            sendJson(true, "Nenhuma avaliação encontrada para este parque.", [
                "place_id" => $placeId,
                "reviews" => [],
                "count" => 0
            ]);
        }
        
        $idParque = $resultParque->fetch_assoc()['idParque'];
        $stmtParque->close();
        
        // Busca as avaliações do parque com informações do usuário
        // **ALTERAÇÃO AQUI: Adicionado 'ap.created_at'**
        $query = "
            SELECT 
                ap.idAvaliacao,
                ap.nota as rating,
                ap.comentario as review_text,
                ap.created_at, 
                u.idUsuario,
                u.nome as user_name,
                u.avatar_path as user_photo
            FROM Avaliacao_parque ap
            INNER JOIN Usuario u ON ap.idUsuario = u.idUsuario
            WHERE ap.idParque = ?
            ORDER BY ap.idAvaliacao DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $idParque);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            // Ajusta o caminho da foto de perfil
            if (!empty($row['user_photo'])) {
                // Se a foto já tem um caminho completo, mantém
                if (strpos($row['user_photo'], 'http') === false && strpos($row['user_photo'], '/') !== 0) {
                    $row['user_photo'] = '/Spark-main/formulario-cadastro-login/' . $row['user_photo'];
                }
            }
            
            // **REMOVIDA a linha da data fictícia. A data real agora está em $row['created_at']**
            
            $reviews[] = $row;
        }
        $stmt->close();

        sendJson(true, "Avaliações carregadas com sucesso.", [
            "place_id" => $placeId,
            "id_parque" => $idParque,
            "reviews" => $reviews,
            "count" => count($reviews)
        ]);
    }

    // ====================================================
    // 2. REQUISIÇÃO POST: Adicionar Nova Avaliação
    // **NÃO PRECISA DE ALTERAÇÃO AQUI** (MySQL gerencia o TIMESTAMP)
    // ====================================================
    elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
        
        // Verifica se o usuário está logado
        if (!isset($_SESSION['id_usuario'])) {
            throw new Exception("Você deve estar logado para avaliar um parque.");
        }
        
        $idUsuario = $_SESSION['id_usuario'];
        $placeId = trim($_POST['place_id'] ?? '');
        $rating = intval($_POST['rating'] ?? 0);
        $reviewText = trim($_POST['review_text'] ?? '');
        $parkName = trim($_POST['park_name'] ?? '');
        $parkAddress = trim($_POST['park_address'] ?? '');
        $parkCep = trim($_POST['park_cep'] ?? '');
        
        // Validações
        if (empty($placeId)) {
            throw new Exception("Place ID do parque não fornecido.");
        }
        
        if ($rating < 1 || $rating > 5) {
            throw new Exception("Classificação deve estar entre 1 e 5 estrelas.");
        }
        
        if (empty($reviewText) || strlen($reviewText) < 10) {
            throw new Exception("A avaliação deve ter pelo menos 10 caracteres.");
        }
        
        // ------------------------------------
        // BUSCA OU CRIA O PARQUE
        // ------------------------------------
        $idParque = null;
        
        // Tenta buscar pelo CEP ou place_id no endereço
        $stmtBusca = $conn->prepare("SELECT idParque FROM Parque WHERE cep = ? OR endereco LIKE ? LIMIT 1");
        $likeAddress = "%{$placeId}%";
        $stmtBusca->bind_param("ss", $parkCep, $likeAddress);
        $stmtBusca->execute();
        $resultBusca = $stmtBusca->get_result();
        
        if ($resultBusca->num_rows > 0) {
            $idParque = $resultBusca->fetch_assoc()['idParque'];
        } else {
            // Cria novo parque
            $stmtInsert = $conn->prepare("INSERT INTO Parque (nome, endereco, cep) VALUES (?, ?, ?)");
            $enderecoCompleto = $parkAddress . " [" . $placeId . "]";
            $stmtInsert->bind_param("sss", $parkName, $enderecoCompleto, $parkCep);
            
            if (!$stmtInsert->execute()) {
                throw new Exception("Erro ao registrar parque: " . $stmtInsert->error);
            }
            
            $idParque = $stmtInsert->insert_id;
            $stmtInsert->close();
        }
        $stmtBusca->close();
        
        // ------------------------------------
        // VERIFICA SE JÁ EXISTE AVALIAÇÃO
        // ------------------------------------
        $checkQuery = "SELECT idAvaliacao FROM Avaliacao_parque WHERE idParque = ? AND idUsuario = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("ii", $idParque, $idUsuario);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Atualiza a avaliação existente
            $stmt->close();
            
            $updateQuery = "
                UPDATE Avaliacao_parque 
                SET nota = ?, comentario = ?
                WHERE idParque = ? AND idUsuario = ?
            ";
            
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("isii", $rating, $reviewText, $idParque, $idUsuario);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao atualizar avaliação: " . $stmt->error);
            }
            
            $message = "Sua avaliação foi atualizada com sucesso!";
        } else {
            // Insere nova avaliação
            $stmt->close();
            
            $insertQuery = "
                INSERT INTO Avaliacao_parque (idUsuario, idParque, nota, comentario)
                VALUES (?, ?, ?, ?)
            ";
            
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("iiis", $idUsuario, $idParque, $rating, $reviewText);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao salvar avaliação: " . $stmt->error);
            }
            
            $message = "Avaliação enviada com sucesso!";
        }
        
        $stmt->close();

        sendJson(true, $message, [
            "place_id" => $placeId,
            "id_parque" => $idParque,
            "rating" => $rating
        ]);

    } else {
        throw new Exception("Método de requisição não suportado.");
    }

} catch (Exception $e) {
    error_log("ERRO em park_reviews.php: " . $e->getMessage());
    if (isset($conn)) $conn->close();
    sendJson(false, $e->getMessage());
}
?>