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

// Prepara e executa a consulta para buscar os dados do usuário
$stmt = $conexao->prepare("SELECT nome, email, avatar_path FROM Usuario WHERE idUsuario = ?");
$stmt->bind_param("i", $idUsuarioLogado);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();

$stmt->close();
$conexao->close();

// Define o caminho do avatar (usa um padrão se o usuário não tiver um)
$avatar = !empty($usuario['avatar_path']) ? $usuario['avatar_path'] : 'assets/images/avatar_padrao.png';
$nome = !empty($usuario['nome']) ? $usuario['nome'] : 'Nome não encontrado';
$email = !empty($usuario['email']) ? $usuario['email'] : 'email@naoencontrado.com';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Spark — Conta</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="teladeusuario.css" />
</head>
<body>

    <header class="appbar">
        <h1>Conta</h1>
    </header>

    <main class="app-content">
        <section id="screen-settings" class="screen active">
        
            <div class="card profile-card">
                <img class="avatar" id="avatarMain" src="/Spark-main/<?php echo htmlspecialchars($avatar); ?>" alt="Foto do perfil" />
                <div class="profile-meta">
                    <span class="hello">Olá,</span>
                    <h2 class="username" id="displayName"><?php echo htmlspecialchars($nome); ?></h2>
                    <span class="email" id="displayEmail"><?php echo htmlspecialchars($email); ?></span>
                </div>
                <button class="pill-btn" onclick="goToEdit()">
                    <i class="fa-solid fa-pen"></i>
                    Editar
                </button>
            </div>

            <div class="list">
                <button class="list-item" onclick="goToEdit()">
                    <div class="li-left"><i class="fa-solid fa-id-card-clip"></i><span>Informações Pessoais</span></div>
                    <i class="fa-solid fa-angle-right"></i>
                </button>

                <button class="list-item"><div class="li-left"><i class="fa-solid fa-envelope"></i><span>Mensagens</span></div><i class="fa-solid fa-angle-right"></i></button>
                <button class="list-item"><div class="li-left"><i class="fa-solid fa-key"></i><span>Alterar Senha</span></div><i class="fa-solid fa-angle-right"></i></button>
                <button class="list-item"><div class="li-left"><i class="fa-solid fa-circle-info"></i><span>Suporte</span></div><i class="fa-solid fa-angle-right"></i></button>

                <button class="list-item danger" onclick="window.location.href='/Spark-main/formulario-cadastro-login/formulario-login/logout.php'">
                        <div class="li-left"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Sair</span></div>
                </button>
            </div>
        </section>

        <section id="screen-edit" class="screen">
            <div class="subbar">
                <button class="icon-btn" onclick="goBack()" aria-label="Voltar"><i class="fa-solid fa-arrow-left"></i></button>
                <h2>Editar Perfil</h2>
                <button class="text-btn" onclick="saveProfile()">Salvar</button>
            </div>
            <div class="edit-wrap">
                </div>
        </section>
    </main>
    
    <nav class="bottombar">
        <button class="nav-btn" onclick="window.location.href='/Spark-main/TelaPerfils/perfil.html'"><i class="fa-solid fa-users"></i></button>
        <button class="nav-btn" onclick="window.location.href='/Spark-main/tela-atividades/atividades.php'"><i class="fa-solid fa-person-walking"></i></button>
        <button class="nav-btn" onclick="window.location.href='/Spark-main/tela-principal/telaprincipal.php'"><i class="fa-solid fa-house"></i></button>
        <button class="nav-btn" onclick="window.location.href='/Spark-main/telaFavoritos/index.html'"><i class="fa-solid fa-star"></i></button>
        <button class="nav-btn active"><i class="fa-solid fa-user"></i></button>
    </nav>

    <script src="script.js"></script>
</body>
</html>
