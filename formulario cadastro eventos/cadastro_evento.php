<?php
// 1. INICIA A SESSÃO
// Esta deve ser a PRIMEIRA linha do seu arquivo, antes de qualquer espaço ou HTML.
session_start();

// 2. VERIFICAÇÃO DE LOGIN ROBUSTA
// Primeiro, verificamos se a variável de sessão sequer existe.
if (!isset($_SESSION['id_usuario']) || empty($_SESSION['id_usuario'])) {
    
    // Se não existir ou estiver vazia, o usuário não está logado.
    // Destruímos qualquer resquício de sessão e o enviamos para o login.
    session_destroy();
    
    // Usamos o caminho absoluto para garantir que ele encontre a página de login.
    $login_url = '/Spark-main/formulario-login/login.php?erro=restrito';
    header("Location: " . $login_url);
    exit(); // Encerra o script imediatamente.
}

// 3. Se o script passou pela verificação, AGORA SIM podemos pegar o ID com segurança.
$idUsuario = $_SESSION['id_usuario'];

// --- O RESTO DO SEU SCRIPT CONTINUA AQUI ---

// 4. Configurações do Banco de Dados
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "spark";

// 5. Criar a Conexão
$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// 6. Receber os dados do formulário
$nomeParque = $_POST['nomeParque'];
$enderecoParque = $_POST['enderecoParque'];
$cepParque = $_POST['cepParque'];
$nomeEvento = $_POST['nomeEvento'];
$dataEvento = $_POST['dataEvento'];
$horaInicio = $_POST['horaInicio'];
$horaTermino = $_POST['horaTermino'];
$descricaoEvento = $_POST['descricaoEvento'];
$imagem_path = null;

// 7. Processamento do Upload de Imagem
if (isset($_FILES['imagemEvento']) && $_FILES['imagemEvento']['error'] === UPLOAD_ERR_OK) {
    $diretorio_uploads = "../uploads/"; // Apontando para a pasta uploads na raiz
    // ... (resto da sua lógica de upload, que já está funcionando) ...
    $info_arquivo = pathinfo($_FILES['imagemEvento']['name']);
    $extensao = strtolower($info_arquivo['extension']);
    $nome_unico = uniqid('evento_', true) . '.' . $extensao;
    $caminho_completo = $diretorio_uploads . $nome_unico;
    if (move_uploaded_file($_FILES['imagemEvento']['tmp_name'], $caminho_completo)) {
        // CORREÇÃO IMPORTANTE: Salvar o caminho a partir da raiz para consistência
        $imagem_path = "uploads/" . $nome_unico;
    }
}

// 8. Lógica para buscar ou criar o Parque
// ... (seu código para o parque, que já está correto, continua aqui) ...
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


// 9. Inserir o Evento no Banco de Dados
if ($idParque) {
    $stmt_evento = $conexao->prepare(
        "INSERT INTO Evento (idParque, idUsuario, nome, dia, horario_inicio, horario_termino, descricao, imagem_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    // A variável $idUsuario agora está garantida de ter o valor correto da sessão
    $stmt_evento->bind_param("iissssss", $idParque, $idUsuario, $nomeEvento, $dataEvento, $horaInicio, $horaTermino, $descricaoEvento, $imagem_path);

    if ($stmt_evento->execute()) {
        // Redirecionamento de sucesso
        $redirect_url = '/Spark-main/tela-atividades/atividades.php';
        echo "<!DOCTYPE html><html><head><title>Sucesso!</title><meta http-equiv='refresh' content='3;url={$redirect_url}'><style>body{font-family:sans-serif;text-align:center;padding-top:50px;}h1{color:green;}</style></head><body><h1>Evento cadastrado com sucesso!</h1><p>Redirecionando...</p></body></html>";
    } else {
        echo "Erro ao cadastrar o evento: " . $stmt_evento->error;
    }
    $stmt_evento->close();
} else {
    echo "Houve um erro ao processar as informações do parque.";
}

// 10. Fechar a conexão
$conexao->close();

?>
