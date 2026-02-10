<?php
// =========================================================================
// excluir-evento.php - Lógica para excluir um evento
// =========================================================================

// Define o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Inclui o guardião de sessão
require_once('../formulario-cadastro-login/verificacao.php');
$idUsuarioLogado = $_SESSION['id_usuario'];

// URL Base
$url_base = '/Spark-main/';

// ------------------------------------
// CONEXÃO COM O BANCO DE DADOS
// ------------------------------------
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "Spark";

$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) {
    die("Erro Crítico: Falha na conexão com o banco de dados.");
}
$conexao->set_charset("utf8mb4");

$idEvento = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Verifica se o ID do evento é válido
if (!$idEvento) {
    $_SESSION['mensagem_erro'] = "ID do evento inválido.";
    header("Location: " . $url_base . "tela-atividades/atividades.php?aba=meus-eventos");
    exit();
}

try {
    // ------------------------------------
    // 1. VERIFICAR PERMISSÃO
    // ------------------------------------
    // Busca o criador do evento para garantir que o usuário logado é o dono
    $sql_check = "SELECT idUsuario FROM Evento WHERE idEvento = ?";
    $stmt_check = $conexao->prepare($sql_check);
    $stmt_check->bind_param("i", $idEvento);
    $stmt_check->execute();
    $resultado_check = $stmt_check->get_result();

    if ($resultado_check->num_rows === 0) {
        throw new Exception("Evento não encontrado.");
    }
    
    $evento = $resultado_check->fetch_assoc();
    $idCriador = $evento['idUsuario'];
    $stmt_check->close();

    if ($idCriador != $idUsuarioLogado) {
        throw new Exception("Você não tem permissão para excluir este evento.");
    }

    // ------------------------------------
    // 2. EXCLUIR REGISTROS DEPENDENTES (Participantes)
    // ------------------------------------
    // IMPORTANTE: Excluir participantes primeiro se a FOREIGN KEY não tiver ON DELETE CASCADE
    // Analisando o Banco-Oficial.sql, a tabela Participantes não tem FOREIGN KEY para Evento.
    // Portanto, o banco não deve bloquear. Mas por segurança, vamos checar se a FK da tabela Evento para Participantes existe.
    // O modelo do banco não possui FK de Participantes. O banco de dados pode ter ON DELETE CASCADE configurado.
    // Assumindo que você precisa deletar manualmente participantes:
    
    // Deletar Participantes
    $sql_participantes = "DELETE FROM Participantes WHERE idEvento = ?";
    $stmt_participantes = $conexao->prepare($sql_participantes);
    $stmt_participantes->bind_param("i", $idEvento);
    $stmt_participantes->execute();
    $stmt_participantes->close();

    // Deletar Avaliacao_evento
    $sql_avaliacoes = "DELETE FROM Avaliacao_evento WHERE idEvento = ?";
    $stmt_avaliacoes = $conexao->prepare($sql_avaliacoes);
    $stmt_avaliacoes->bind_param("i", $idEvento);
    $stmt_avaliacoes->execute();
    $stmt_avaliacoes->close();
    
    // ------------------------------------
    // 3. EXCLUIR O EVENTO PRINCIPAL
    // ------------------------------------
    $conexao->begin_transaction(); // Inicia a transação

    $sql_delete = "DELETE FROM Evento WHERE idEvento = ?";
    $stmt_delete = $conexao->prepare($sql_delete);
    $stmt_delete->bind_param("i", $idEvento);
    
    if (!$stmt_delete->execute()) {
        $conexao->rollback();
        throw new Exception("Falha ao excluir o evento principal: " . $stmt_delete->error);
    }

    $conexao->commit(); // Confirma a transação
    $stmt_delete->close();
    
    $_SESSION['mensagem_sucesso'] = "Evento excluído com sucesso!";

} catch (Exception $e) {
    // Se o rollback não foi chamado, não precisa chamar novamente.
    if (isset($conexao) && $conexao->in_transaction) {
         $conexao->rollback();
    }
    error_log("Erro ao excluir evento ID {$idEvento}: " . $e->getMessage());
    $_SESSION['mensagem_erro'] = "Erro ao excluir o evento: " . $e->getMessage();

} finally {
    if (isset($conexao)) {
        $conexao->close();
    }
}

// Redireciona de volta para a aba "Meus Eventos"
header("Location: " . $url_base . "tela-atividades/atividades.php?aba=meus-eventos");
exit();
?>