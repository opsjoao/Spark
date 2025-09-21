<?php
// Substitua o session_start() e o if(!isset(...)) por esta linha
require_once('../formulario-cadastro-evento/verificacao.php');

// Pega o ID do usuário da sessão
$idUsuarioLogado = $_SESSION['id_usuario'];

// 1. Configurações do Banco de Dados
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "spark";

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

// 4. PROCESSAMENTO DE UPLOAD DE IMAGEM (VERSÃO MELHORADA)
// Verifica se um arquivo foi realmente enviado e se não houve erros de upload
if (isset($_FILES['imagemEvento']) && $_FILES['imagemEvento']['error'] === UPLOAD_ERR_OK) {
    
    $diretorio_uploads = "uploads/";
    
    // Garante que a pasta uploads exista. Se não, tenta criar.
    if (!is_dir($diretorio_uploads)) {
        // O @ suprime erros caso a pasta já exista, o que é normal.
        @mkdir($diretorio_uploads, 0777, true);
    }

    // Verifica se a pasta de uploads pode ser escrita
    if (!is_writable($diretorio_uploads)) {
        die("ERRO CRÍTICO: O servidor não tem permissão para escrever na pasta '{$diretorio_uploads}'. Verifique as permissões da pasta.");
    }

    $info_arquivo = pathinfo($_FILES['imagemEvento']['name']);
    $extensao = strtolower($info_arquivo['extension']);
    $nome_unico = uniqid('evento_', true) . '.' . $extensao;
    $caminho_completo = $diretorio_uploads . $nome_unico;

    // Validações de segurança
    $tamanho_maximo = 5 * 1024 * 1024; // 5 MB
    $tipos_permitidos = ['jpg', 'jpeg', 'png', 'gif'];
    
    if ($_FILES['imagemEvento']['size'] > $tamanho_maximo) {
        die("Erro: O arquivo de imagem é muito grande. O máximo permitido é 5MB.");
    }

    if (!in_array($extensao, $tipos_permitidos)) {
        die("Erro: Tipo de arquivo não permitido. Apenas imagens JPG, JPEG, PNG e GIF são aceitas.");
    }
    
    // Tenta mover o arquivo e verifica se o processo foi bem-sucedido
    if (move_uploaded_file($_FILES['imagemEvento']['tmp_name'], $caminho_completo)) {
        // Sucesso! Define o caminho para ser salvo no banco.
        $imagem_path = $caminho_completo;
    } else {
        // Falha! Exibe uma mensagem de erro clara.
        die("ERRO CRÍTICO: Não foi possível mover o arquivo para a pasta de uploads. O upload falhou.");
    }
}

// 5. Lógica para buscar ou criar o Parque
// ... (seu código para o parque continua aqui, sem alterações) ...
$stmt_parque = $conexao->prepare("SELECT idParque FROM Parque WHERE nome = ?");
$stmt_parque->bind_param("s", $nomeParque);
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

// 6. Inserir o Evento no Banco de Dados
if ($idParque) {
    $stmt_evento = $conexao->prepare(
        "INSERT INTO Evento (idParque, idUsuario, nome, dia, horario_inicio, horario_termino, descricao, imagem_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
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

// 7. Fechar a conexão
$conexao->close();

?>
