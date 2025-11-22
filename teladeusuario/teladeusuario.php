<?php
// Inclui o nosso guardião, que já faz o session_start() e a verificação.
require_once('../formulario-cadastro-login/verificacao.php');

// Pega o ID do usuário da sessão
$idUsuarioLogado = $_SESSION['id_usuario'];

// --- BUSCANDO DADOS DO USUÁRIO NO BANCO ---
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "Spark";

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
<a href="<?php echo $url_base; ?>tela-atividades/atividades.php?aba=meus-eventos">
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
<a href="<?php echo $url_base; ?>teladeusuario/teladeusuario/contato.php">

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
  <button class="nav-btn" onclick="window.location.href='../telas-mapas/index.html'">
  <svg version="1.0" xmlns="http://www.w3.org/2000/svg"
  width="28px" height="28px" viewBox="0 0 512.000000 512.000000"
  preserveAspectRatio="xMidYMid meet">

  <g transform="translate(0.000000,512.000000) scale(0.100000,-0.100000)"
  fill="#333" stroke="none">
  <path d="M3880 5114 c-253 -46 -368 -91 -527 -203 -320 -226 -515 -647 -464
  -1002 50 -348 378 -896 903 -1510 135 -159 185 -185 274 -143 54 26 304 324
  523 624 372 510 549 910 528 1190 -40 510 -395 920 -892 1025 -77 17 -294 28
  -345 19z m221 -648 c94 -20 164 -59 236 -132 188 -187 187 -481 -1 -670 -189
  -189 -483 -189 -672 0 -188 189 -189 483 -1 670 121 122 271 167 438 132z"/>
  <path d="M836 3535 c-543 -218 -767 -312 -788 -332 -15 -15 -33 -44 -38 -64
  -7 -24 -9 -566 -8 -1535 l3 -1499 24 -34 c26 -36 90 -70 133 -71 15 0 345 127
  733 282 l705 282 0 1638 c0 901 -1 1638 -2 1638 -2 -1 -345 -138 -762 -305z"/>
  <path d="M1920 2202 l0 -1638 640 -256 640 -256 0 1285 0 1284 -69 98 c-185
  261 -346 534 -431 731 l-41 95 -337 134 c-185 74 -352 141 -369 148 l-33 13 0
  -1638z"/>
  <path d="M5076 3029 c-192 -313 -602 -845 -766 -993 -95 -86 -248 -129 -382
  -108 -122 18 -201 65 -312 185 l-96 102 0 -1107 0 -1108 48 19 c26 10 370 148
  764 306 791 316 770 305 783 397 3 24 4 567 3 1207 l-3 1164 -39 -64z"/>
  </g>
  </svg>
  </button>
<button class="nav-btn active">
<i class="fa-solid fa-user"></i>
</button>
</nav>
</body>
</html>




