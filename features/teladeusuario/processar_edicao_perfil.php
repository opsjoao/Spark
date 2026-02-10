<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ../formulario-login/formulario-login.php');
    exit();
}

$idUsuarioLogado = $_SESSION['id_usuario'];

$servidor = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "spark";

$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// ====================================================================================
// DEFINA A SUA URL BASE CORRETA AQUI
// Substitua '/Spark-main/' pelo valor exato que o seu script 'teste_caminho.php' mostrou.
$url_base = '/Spark-main/';
// ====================================================================================

$nome = $_POST['nome'];
$username = $_POST['username'];
$email = $_POST['email']; // O e-mail é readonly, mas passamos por segurança.

$avatar_path = null; // Caminho da nova imagem (se houver)

// Lidar com o upload da imagem
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK) {
    $target_dir = $_SERVER['DOCUMENT_ROOT'] . $url_base . "assets/uploads/avatars/";
    
    // Cria a pasta se não existir
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $new_file_name = uniqid('avatar_') . '.' . $file_extension;
    $target_file = $target_dir . $new_file_name;

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
        // Salva o caminho relativo ao root do servidor web no banco de dados
        $avatar_path = "assets/uploads/avatars/" . $new_file_name;

        // Se o usuário tinha um avatar antigo, podemos tentar excluí-lo
        $stmt_old_avatar = $conexao->prepare("SELECT avatar_path FROM Usuario WHERE idUsuario = ?");
        $stmt_old_avatar->bind_param("i", $idUsuarioLogado);
        $stmt_old_avatar->execute();
        $resultado_old_avatar = $stmt_old_avatar->get_result();
        if ($old_avatar_data = $resultado_old_avatar->fetch_assoc()) {
            $old_avatar_full_path = $_SERVER['DOCUMENT_ROOT'] . $url_base . $old_avatar_data['avatar_path'];
            if (file_exists($old_avatar_full_path) && strpos($old_avatar_full_path, 'avatar_padrao.png') === false) { // Evita deletar o avatar padrão
                unlink($old_avatar_full_path);
            }
        }
        $stmt_old_avatar->close();

    } else {
        // Erro no upload, você pode tratar isso de forma mais robusta
        $_SESSION['message'] = "Erro ao fazer upload da imagem.";
        header("Location: " . $_SERVER['HTTP_REFERER']); // Volta para a página anterior
        exit();
    }
}

// Preparar a query de atualização
if ($avatar_path) {
    $stmt = $conexao->prepare("UPDATE Usuario SET nome = ?, username = ?, avatar_path = ? WHERE idUsuario = ?");
    $stmt->bind_param("sssi", $nome, $username, $avatar_path, $idUsuarioLogado);
} else {
    $stmt = $conexao->prepare("UPDATE Usuario SET nome = ?, username = ? WHERE idUsuario = ?");
    $stmt->bind_param("ssi", $nome, $username, $idUsuarioLogado);
}

if ($stmt->execute()) {
    $_SESSION['message'] = "Perfil atualizado com sucesso!";
    // Redireciona de volta para a tela de conta
    header("Location: " . $url_base . "teladeusuario/teladeusuario.php");
} else {
    $_SESSION['message'] = "Erro ao atualizar perfil: " . $conexao->error;
    header("Location: " . $_SERVER['HTTP_REFERER']); // Volta para a página anterior
}

$stmt->close();
$conexao->close();
exit();
?>