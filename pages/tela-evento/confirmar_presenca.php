<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    // Se o usuário não estiver logado, não pode confirmar presença
    header("Location: /Spark-main/formulario-cadastro-login/formulario-login/login.php?erro=restrito");
    exit();
}

if (!isset($_POST['idEvento']) || !is_numeric($_POST['idEvento'])) {
    // Se o ID do evento não for enviado, volta para a página de atividades
    header("Location: /Spark-main/tela-atividades/atividades.php");
    exit();
}

$idUsuario = $_SESSION['id_usuario'];
$idEvento = $_POST['idEvento'];

$servidor = "localhost"; $usuario_db = "root"; $senha_db = ""; $banco = "Spark";
$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) { die("Falha na conexão: " . $conexao->connect_error); }

// 1. Verifica se o usuário JÁ ESTÁ inscrito no evento para não duplicar
$stmt_check = $conexao->prepare("SELECT idUsuario FROM Participantes WHERE idUsuario = ? AND idEvento = ?");
$stmt_check->bind_param("ii", $idUsuario, $idEvento);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    // Se já está inscrito, apenas redireciona de volta com um aviso
    header("Location: tela-evento.php?id=" . $idEvento . "&status=ja_inscrito");
    exit();
}
$stmt_check->close();

// 2. Se não está inscrito, insere o novo registro na tabela Participantes
$stmt_insert = $conexao->prepare("
    INSERT INTO Participantes (idUsuario, idEvento, dataInscricao, dataParticipacao) 
    VALUES (?, ?, CURDATE(), CURDATE())
");
// Nota: dataParticipacao talvez devesse ser a data do evento, mas usamos a data de hoje para simplificar a lógica.
$stmt_insert->bind_param("ii", $idUsuario, $idEvento);

if ($stmt_insert->execute()) {
    // Redireciona de volta com mensagem de sucesso
    header("Location: tela-evento.php?id=" . $idEvento . "&status=confirmado");
    exit();
} else {
    // Redireciona de volta com mensagem de erro
    header("Location: tela-evento.php?id=" . $idEvento . "&status=erro");
    exit();
}

$stmt_insert->close();
$conexao->close();

?>
