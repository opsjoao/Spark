<?php
// Inclui o nosso guardião, que já faz o session_start() e a verificação.
require_once('../formulario-cadastro-login/verificacao.php');

// Pega o ID do usuário da sessão
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

$stmt = $conexao->prepare("SELECT nome, avatar_path FROM Usuario WHERE idUsuario = ?");
$stmt->bind_param("i", $idUsuarioLogado);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

$stmt->close();
$conexao->close();

$url_base = '/Spark-main/';

// Define as variáveis para usar no HTML
$avatar = !empty($usuario['avatar_path']) ? $url_base . $usuario['avatar_path'] : $url_base . 'assets/images/avatar_padrao.png';
$nome = !empty($usuario['nome']) ? $usuario['nome'] : 'Usuário';
$logout_url = $url_base . 'formulario-cadastro-login/formulario-login/logout.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Spark — Conta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <link rel="stylesheet" href="<?php echo $url_base; ?>style.css" />
    
    <link rel="stylesheet" href="<?php echo $url_base; ?>teladeusuario/teladeusuario.css" />
</head>
<body>
    <header class="user-header">
        <h1>Conta</h1>
        <div class="user-info">
            <img class="avatar" src="<?php echo htmlspecialchars($avatar); ?>" alt="Foto do perfil" />
            <div class="user-meta">
                <span>Bem vindo,</span>
                <h2><?php echo htmlspecialchars($nome); ?></h2>
            </div>
            <a href="<?php echo $url_base; ?>teladeusuario/editar_perfil.php" class="edit-link" aria-label="Editar Perfil">
                <div class="item-content">
                    <i class="fa-solid fa-pen-to-square icon-circle"></i>
</div>
            </a>
        </div>
    </header>

    <main class="app-content">
        <div class="settings-list">
            <a href="#">
                <div class="settings-item">
                    <div class="item-content">
                        <i class="fa-solid fa-person-walking icon-circle"></i>
                        <span>Meus Eventos</span>
                    </div>
                    <i class="angle fa-solid fa-angle-right"></i>
                </div>
            </a>
            <a href="#">
                <div class="settings-item">
                    <div class="item-content">
                        <i class="fa-solid fa-star icon-circle"></i>
                        <span>Minhas Avaliações</span>
                    </div>
                    <i class="angle fa-solid fa-angle-right"></i>
                </div>
            </a>

            <div class="settings-divisor" ></div>

            <a href="<?php echo $url_base; ?>teladeusuario/alterar_senha.php">
                <div class="settings-item">
                    <div class="item-content">
                        <i class="fa-solid fa-key icon-circle"></i>
                        <span>Alterar Senha</span>
                    </div>
                    <i class="angle fa-solid fa-angle-right"></i>
                </div>
            </a>
            <a href="#">
                 <div class="settings-item">
                    <div class="item-content">
                        <i class="fa-solid fa-circle-info icon-circle"></i>
                        <span>Suporte</span>
                    </div>
                    <i class="angle fa-solid fa-angle-right"></i>
                </div>
            </a>
             <a href="<?php echo $logout_url; ?>">
                <div class="settings-item danger">
                    <div class="item-content">
                        <i class="fa-solid fa-arrow-right-from-bracket icon-circle"></i>
                        <span>Sair da Conta</span>
                    </div>
                    <i class="angle fa-solid fa-angle-left"></i>
                </div>
            </a>
        </div>
    </main>
    
    <nav class="bottombar">
        <button class="nav-btn" onclick="window.location.href='<?php echo $url_base; ?>TelaPerfils/perfil.html'">
            <i class="fa-solid fa-users"></i>
        </button>
        <button class="nav-btn" onclick="window.location.href='<?php echo $url_base; ?>tela-atividades/atividades.php'">
            <i class="fa-solid fa-person-walking"></i>
        </button>
        <button class="nav-btn" onclick="window.location.href='<?php echo $url_base; ?>tela-principal/telaprincipal.php'">
            <i class="fa-solid fa-house"></i>
        </button>
        <button class="nav-btn" onclick="window.location.href='<?php echo $url_base; ?>telaFavoritos/index.html'">
            <i class="fa-solid fa-star"></i>
        </button>
        <button class="nav-btn active">
            <i class="fa-solid fa-user"></i>
        </button>
    </nav>
</body>
</html>
