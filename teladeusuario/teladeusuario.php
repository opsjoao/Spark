<?php
// Inclui o nosso guardião. Ele já faz o session_start() e a verificação.
require_once('../formulario-cadastro-login/verificacao.php');

// Pega o ID do usuário da sessão (agora temos certeza que ele existe)
$idUsuarioLogado = $_SESSION['id_usuario'];

// --- BUSCANDO DADOS DO USUÁRIO NO BANCO ---
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "spark";

$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// Prepara e executa a consulta para buscar os dados do usuário, incluindo o username
$stmt = $conexao->prepare("SELECT nome, username, email, avatar_path FROM Usuario WHERE idUsuario = ?");
$stmt->bind_param("i", $idUsuarioLogado);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

$stmt->close();
$conexao->close();

$url_base = '/Spark-main/';

// Define as variáveis para usar no HTML
$avatar = !empty($usuario['avatar_path']) ? $usuario['avatar_path'] : 'assets/images/avatar_padrao.png';
$nome = !empty($usuario['nome']) ? $usuario['nome'] : 'Nome não encontrado';
$username = !empty($usuario['username']) ? $usuario['username'] : 'username';
$logout_url = '/Spark-main/formulario-cadastro-login/formulario-login/logout.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Spark — Conta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="/Spark-main/teladeusuario/teladeusuario.css" />
</head>
<body>
    <header class="appbar">
        <h1>Minha Conta</h1>
    </header>

    <main class="app-content">
        <div class="card profile-card">
            <img class="avatar" id="avatarMain" src="/Spark-main/<?php echo htmlspecialchars($avatar); ?>" alt="Foto do perfil" />
            <div class="profile-meta">
                <h2 class="username" id="displayName"><?php echo htmlspecialchars($nome); ?></h2>
                <span class="email" id="displayUsername">@<?php echo htmlspecialchars($username); ?></span>
            </div>
            <a href="<?php echo $url_base; ?>teladeusuario/editar_perfil.php" class="pill-btn">
                <i class="fa-solid fa-pen"></i>
                Editar Perfil
            </a>
        </div>

        <div class="list-group">
            <h3 class="list-group-title">Minha Atividade</h3>
            <a href="/Spark-main/tela-atividades/atividades.php?aba=meus-eventos" class="list-item">
                <div class="li-left">
                    <i class="fa-solid fa-person-walking icon-circle"></i>
                    <span>Meus Eventos</span>
                </div>
                <i class="fa-solid fa-angle-right"></i>
            </a>
             <a href="#" class="list-item">
                <div class="li-left">
                    <i class="fa-solid fa-star icon-circle"></i>
                    <span>Minhas Avaliações</span>
                </div>
                <i class="fa-solid fa-angle-right"></i>
            </a>
        </div>
        
       <div class="list-group">
            <h3 class="list-group-title">Configurações</h3>
            
            <a href="<?php echo $url_base; ?>teladeusuario/alterar_senha.php" class="list-item">
                <div class="li-left">
                    <i class="fa-solid fa-key icon-circle"></i>
                    <span>Alterar Senha</span>
                </div>
                <i class="fa-solid fa-angle-right"></i>
            </a>
            
            <a href="#" class="list-item">
                <div class="li-left">
                    <i class="fa-solid fa-circle-info icon-circle"></i>
                    <span>Suporte</span>
                </div>
                <i class="fa-solid fa-angle-right"></i>
            </a>
        </div>

        <a href="<?php echo $logout_url; ?>" class="list-item danger">
            <div class="li-left">   
                <i class="fa-solid fa-arrow-right-from-bracket icon-circle"></i>
                <span>Sair da Conta</span>
            </div>
        </a>
    </main>
    
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