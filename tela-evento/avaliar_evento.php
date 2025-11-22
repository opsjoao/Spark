<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { die("Acesso negado."); }

$idUsuario = $_SESSION['id_usuario'];
$idEvento = $_POST['idEvento'];
$nota = $_POST['nota'];
$comentario = $_POST['comentario'];
$imagem_path = null;

// Lógica de Upload da Imagem
if (isset($_FILES['imagemAvaliacao']) && $_FILES['imagemAvaliacao']['error'] === UPLOAD_ERR_OK) {
    $diretorio_uploads = "../uploads/avaliacoes/";
    if (!is_dir($diretorio_uploads)) { @mkdir($diretorio_uploads, 0777, true); }
    
    $info_arquivo = pathinfo($_FILES['imagemAvaliacao']['name']);
    $extensao = strtolower($info_arquivo['extension']);
    $nome_unico = uniqid('avaliacao_', true) . '.' . $extensao;
    $caminho_completo = $diretorio_uploads . $nome_unico;

    if (move_uploaded_file($_FILES['imagemAvaliacao']['tmp_name'], $caminho_completo)) {
        $imagem_path = "uploads/avaliacoes/" . $nome_unico;
    }
}

// Conexão e Transação no Banco
$servidor = "localhost"; $usuario_db = "root"; $senha_db = ""; $banco = "Spark";
$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) { die("Falha na conexão."); }

$conexao->begin_transaction();
try {
    // 1. Insere a avaliação na nova tabela
    $stmt_av = $conexao->prepare("INSERT INTO Avaliacao_evento (idEvento, idUsuario, nota, comentario, imagem_path) VALUES (?, ?, ?, ?, ?)");
    $stmt_av->bind_param("iiiss", $idEvento, $idUsuario, $nota, $comentario, $imagem_path);
    $stmt_av->execute();
    $stmt_av->close();

    // 2. Atualiza o status na tabela Participantes para 'participou'
    $stmt_up = $conexao->prepare("UPDATE Participantes SET status = 'participou', dataParticipacao = CURDATE() WHERE idUsuario = ? AND idEvento = ?");
    $stmt_up->bind_param("ii", $idUsuario, $idEvento);
    $stmt_up->execute();
    $stmt_up->close();

    $conexao->commit();
    header("Location: tela-evento.php?id=" . $idEvento . "&status=avaliado");

} catch (mysqli_sql_exception $exception) {
    $conexao->rollback();
    header("Location: tela-evento.php?id=" . $idEvento . "&status=erro_avaliacao");
}

$conexao->close();
exit();

?>
