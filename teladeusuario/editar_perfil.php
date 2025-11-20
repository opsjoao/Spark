<?php
require_once('../formulario-cadastro-login/verificacao.php');

$idUsuarioLogado = $_SESSION['id_usuario'];

$servidor = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "spark";

$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// Buscar dados do usuário para pré-preencher o formulário
$stmt = $conexao->prepare("SELECT nome, username, email, avatar_path FROM Usuario WHERE idUsuario = ?");
$stmt->bind_param("i", $idUsuarioLogado);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

$stmt->close();
$conexao->close();

$nomeAtual = htmlspecialchars($usuario['nome'] ?? '');
$usernameAtual = htmlspecialchars($usuario['username'] ?? '');
$emailAtual = htmlspecialchars($usuario['email'] ?? '');
$avatarAtual = !empty($usuario['avatar_path']) ? $usuario['avatar_path'] : 'assets/images/avatar_padrao.png';

// ====================================================================================
// DEFINA A SUA URL BASE CORRETA AQUI
// Substitua '/Spark-main/' pelo valor exato que o seu script 'teste_caminho.php' mostrou.
$url_base = '/Spark-main/';
// ====================================================================================

$processar_edicao_url = $url_base . 'teladeusuario/processar_edicao_perfil.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Spark — Editar Perfil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $url_base; ?>style.css" />
    <link rel="stylesheet" href="<?php echo $url_base; ?>teladeusuario/editar_perfil.css" />
</head>
<body>
    <header class="appbar edit-appbar">
        <button class="icon-btn" onclick="window.history.back()" aria-label="Voltar"><i class="fa-solid fa-arrow-left"></i></button>
        <h1>Editar Perfil</h1>

        <div class="profile-edit-section">
            <div class="avatar-container"> 
                <img class="avatar-large" id="avatarPreview" src="<?php echo $url_base . $avatarAtual; ?>" alt="Foto do perfil" />
                
                <label for="avatarUpload" class="btn-upload-avatar">
                    <i class="fa-solid fa-camera"></i>
                </label>
            </div>
</div>
    </header>

    <main class="app-content">
        <form id="editProfileForm" action="<?php echo $processar_edicao_url; ?>" method="POST" enctype="multipart/form-data">

            <input type="file" id="avatarUpload" name="avatar" accept="image/*" style="display: none;">
            
            <div class="form-field-group">
                <label for="nome">Nome</label>
                <input type="text" id="nome" name="nome" value="<?php echo $nomeAtual; ?>" required>
            </div>

            <div class="form-field-group">
                <label for="username">Nome de Usuário</label>
                <input type="text" id="username" name="username" value="<?php echo $usernameAtual; ?>" required>
            </div>
            
            <div class="form-field-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" value="<?php echo $emailAtual; ?>" readonly>
            </div>

            <button type="submit" class="btn-primary-large">Salvar Alterações</button>
        </form>
    </main>
    
    <script>
        // Pré-visualização da imagem
        document.getElementById('avatarUpload').addEventListener('change', function(event) {
            const [file] = event.target.files;
            if (file) {
                document.getElementById('avatarPreview').src = URL.createObjectURL(file);
            }
        });
    </script>

    <nav class="bottombar">
        <button class="nav-btn" onclick="window.location.href='/Spark-main/TelaPerfils/perfil.html'">
            <i class="fa-solid fa-users"></i>
        </button>
        <button class="nav-btn" onclick="window.location.href='/Spark-main/tela-atividades/atividades.php'">
            <i class="fa-solid fa-person-walking"></i>
        </button>
        <button class="nav-btn" onclick="window.location.href='/Spark-main/tela-principal/telaprincipal.php'">
            <i class="fa-solid fa-house"></i>
        </button>
        <button class="nav-btn" onclick="window.location.href='/Spark-main/telaFavoritos/index.html'">
            <i class="fa-solid fa-star"></i>
        </button>
        <button class="nav-btn active">
            <i class="fa-solid fa-user"></i>
        </button>
    </nav>
</body>
</html>
