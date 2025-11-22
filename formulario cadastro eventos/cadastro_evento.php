<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['id_usuario'])) {
    $login_url = '/Spark-main/formulario-login/login.php?erro=restrito';
    header("Location: " . $login_url);
    exit();
}
$idUsuario = $_SESSION['id_usuario'];

// 1. Configurações do Banco de Dados
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "Spark";

// 2. Criar a Conexão
$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// 3. Receber os dados do formulário
$nomeParque = $_POST['nomeParque'];
$enderecoParque = $_POST['enderecoParque'];
$cepParque = $_POST['cepParque'];
$nomeEvento = $_POST['nomeEvento'];
$dataEvento = $_POST['dataEvento'];
$horaInicio = $_POST['horaInicio'];
$horaTermino = $_POST['horaTermino'];
$descricaoEvento = $_POST['descricaoEvento'];
$imagem_path = null;

// 4. Processamento do Upload de Imagem
if (isset($_FILES['imagemEvento']) && $_FILES['imagemEvento']['error'] === UPLOAD_ERR_OK) {
    $diretorio_uploads = "../uploads/";
    if (!is_dir($diretorio_uploads)) { @mkdir($diretorio_uploads, 0777, true); }
    
    if (!is_writable($diretorio_uploads)) {
        die("ERRO CRÍTICO: O servidor não tem permissão para escrever na pasta '{$diretorio_uploads}'.");
    }

    $info_arquivo = pathinfo($_FILES['imagemEvento']['name']);
    $extensao = strtolower($info_arquivo['extension']);
    $nome_unico = uniqid('evento_', true) . '.' . $extensao;
    $caminho_completo = $diretorio_uploads . $nome_unico;
    
    if (move_uploaded_file($_FILES['imagemEvento']['tmp_name'], $caminho_completo)) {
        $imagem_path = "uploads/" . $nome_unico;
    }
}

// 5. Lógica para buscar ou criar o Parque
$stmt_parque = $conexao->prepare("SELECT idParque FROM Parque WHERE endereco = ?");
$stmt_parque->bind_param("s", $enderecoParque);
$stmt_parque->execute();
$resultado_parque = $stmt_parque->get_result();
$idParque = null;

if ($resultado_parque->num_rows > 0) {
    $parque = $resultado_parque->fetch_assoc();
    $idParque = $parque['idParque'];
} else {
    $stmt_novo_parque = $conexao->prepare("INSERT INTO Parque (nome, endereco, cep) VALUES (?, ?, ?)");
    $stmt_novo_parque->bind_param("sss", $nomeParque, $enderecoParque, $cepParque);
    if ($stmt_novo_parque->execute()) {
        $idParque = $conexao->insert_id;
    } else {
        die("Erro ao cadastrar o novo parque: " . $stmt_novo_parque->error);
    }
    $stmt_novo_parque->close();
}
$stmt_parque->close();


// 6. Inserir o Evento e a Participação do Criador (com Transação)
if ($idParque) {
    // Inicia a transação
    $conexao->begin_transaction();

    try {
        // Primeiro, insere o evento
        $stmt_evento = $conexao->prepare(
            "INSERT INTO Evento (idParque, idUsuario, nome, dia, horario_inicio, horario_termino, descricao, imagem_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt_evento->bind_param("iissssss", $idParque, $idUsuario, $nomeEvento, $dataEvento, $horaInicio, $horaTermino, $descricaoEvento, $imagem_path);
        $stmt_evento->execute();

        // Pega o ID do evento que acabamos de criar
        $idNovoEvento = $conexao->insert_id;

        // Segundo, insere o criador na tabela de participantes para este novo evento
        $stmt_participante = $conexao->prepare(
            "INSERT INTO Participantes (idUsuario, idEvento, dataInscricao, dataParticipacao, status) VALUES (?, ?, CURDATE(), CURDATE(), 'inscrito')"
        );
        $stmt_participante->bind_param("ii", $idUsuario, $idNovoEvento);
        $stmt_participante->execute();

        // Se ambos os comandos funcionaram, confirma as alterações no banco
        $conexao->commit();

        // Redireciona para a página de atividades
        $redirect_url = '/Spark-main/tela-atividades/atividades.php';
        echo "<!DOCTYPE html><html><head><title>Sucesso!</title><meta http-equiv='refresh' content='3;url={$redirect_url}'><style>body{font-family:sans-serif;text-align:center;padding-top:50px;}h1{color:green;}</style></head><body><h1>Evento cadastrado com sucesso!</h1><p>Você foi automaticamente inscrito no evento.</p><p>Redirecionando...</p></body></html>";

    } catch (mysqli_sql_exception $exception) {
        // Se qualquer um dos comandos falhar, desfaz todas as alterações
        $conexao->rollback();
        die("Erro ao cadastrar o evento e a participação: " . $exception->getMessage());
    } finally {
        if (isset($stmt_evento)) $stmt_evento->close();
        if (isset($stmt_participante)) $stmt_participante->close();
    }
} else {
    echo "Houve um erro ao processar as informações do parque.";
}

// 7. Fechar a conexão
$conexao->close();
?>

