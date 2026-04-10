<?php
session_start();

$host = 'localhost'; $db = 'Spark'; $user = 'root'; $pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Conexão falhou: " . $conn->connect_error); }

$url_base = '/Spark-main/'; 

if (!isset($_SESSION['id_usuario'])) {
    header("Location: /Spark-main/formulario-login/login.php?erro=restrito");
    exit();
}

$meuId = $_SESSION['id_usuario'];
$perfilId = isset($_GET['id']) ? intval($_GET['id']) : $meuId;

$sqlUser = "SELECT * FROM Usuario WHERE idUsuario = ?";
$stmt = $conn->prepare($sqlUser);
$stmt->bind_param("i", $perfilId);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows === 0) { die("Usuário não encontrado."); }
$perfil = $userResult->fetch_assoc();

$perfil['avatar'] = !empty($perfil['avatar_path']) 
    ? $url_base . $perfil['avatar_path'] 
    : $url_base . 'assets/images/avatar_padrao.png';
$anoCadastro = date('Y', strtotime($perfil['data_cadastro']));

$sqlSeguindo = "SELECT COUNT(*) as total FROM Seguidores WHERE idSeguidor = ?";
$stmt = $conn->prepare($sqlSeguindo);
$stmt->bind_param("i", $perfilId);
$stmt->execute();
$totalSeguindo = $stmt->get_result()->fetch_assoc()['total'];

$sqlSeguidores = "SELECT COUNT(*) as total FROM Seguidores WHERE idSeguido = ?";
$stmt = $conn->prepare($sqlSeguidores);
$stmt->bind_param("i", $perfilId);
$stmt->execute();
$totalSeguidores = $stmt->get_result()->fetch_assoc()['total'];

$sigoEle = false;
if ($meuId !== $perfilId) {
    $sqlCheck = "SELECT id FROM Seguidores WHERE idSeguidor = ? AND idSeguido = ?";
    $stmt = $conn->prepare($sqlCheck);
    $stmt->bind_param("ii", $meuId, $perfilId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) { $sigoEle = true; }
}

$eventosCriados = [];
$sqlCriados = "SELECT * FROM Evento WHERE idUsuario = ? ORDER BY dia ASC";
$stmt = $conn->prepare($sqlCriados);
$stmt->bind_param("i", $perfilId);
$stmt->execute();
$resCriados = $stmt->get_result();
while($e = $resCriados->fetch_assoc()) { $eventosCriados[] = $e; }

$eventosParticipa = [];
$sqlParticipa = "SELECT e.* FROM Evento e JOIN Participantes p ON e.idEvento = p.idEvento WHERE p.idUsuario = ? ORDER BY e.dia ASC";
$stmt = $conn->prepare($sqlParticipa);
$stmt->bind_param("i", $perfilId);
$stmt->execute();
$resParticipa = $stmt->get_result();
while($e = $resParticipa->fetch_assoc()) { $eventosParticipa[] = $e; }

$conn->close();

function formatarData($data) { return date('d/m', strtotime($data)); }
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title><?php echo $perfil['nome']; ?> - Spark</title>
  <link rel="stylesheet" href="<?php echo $url_base; ?>style.css" />
  <link rel="stylesheet" href="usuario.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" referrerpolicy="no-referrer"/>
</head>
<body>
  <header class="profile-header">
    <div class="header-top">
        <button class="back-btn" onclick="goBack()"><i class="fa-solid fa-arrow-left"></i></button>
    </div>
    <div class="avatar-container">
        <img src="<?php echo $perfil['avatar']; ?>" alt="Avatar" class="profile-avatar">
    </div>
  </header>

  <main class="profile-content">
    <h2 class="profile-name"><?php echo $perfil['nome']; ?></h2>
    <p class="profile-username">@<?php echo $perfil['username']; ?></p>
    <p class="member-since"><i class="fa-regular fa-calendar"></i> Membro desde <?php echo $anoCadastro; ?></p>

    <div class="stats-row">
        <div class="stat-item">
            <span class="stat-number" id="count-seguidores"><?php echo $totalSeguidores; ?></span>
            <span class="stat-label">Seguidores</span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo $totalSeguindo; ?></span>
            <span class="stat-label">Seguindo</span>
        </div>
    </div>

    <div class="action-area">
        <?php if ($meuId === $perfilId): ?>
            <button class="btn-action btn-edit" onclick="alert('Editar Perfil')">Editar Perfil</button>
        <?php else: ?>
            <button id="btn-follow-profile" 
                    class="btn-action <?php echo $sigoEle ? 'btn-seguindo' : 'btn-seguir'; ?>" 
                    onclick="toggleSeguirProfile(<?php echo $perfilId; ?>)">
                <?php echo $sigoEle ? 'Seguindo' : 'Seguir'; ?>
            </button>
        <?php endif; ?>
    </div>
    <hr class="divider">
    <div class="events-container">
        <div class="event-section">
            <h3 class="section-title title-created"><i class="fa-solid fa-crown"></i> Eventos que Organizo</h3>
            <?php if (count($eventosCriados) > 0): ?>
                <div class="cards-grid">
                    <?php foreach($eventosCriados as $evt): ?>
                        <div class="event-card card-created" onclick="verEvento(<?php echo $evt['idEvento']; ?>)">
                            <div class="card-date"><span class="day"><?php echo formatarData($evt['dia']); ?></span></div>
                            <div class="card-info">
                                <h4><?php echo htmlspecialchars($evt['nome']); ?></h4>
                                <span class="time"><i class="fa-regular fa-clock"></i> <?php echo substr($evt['horario_inicio'], 0, 5); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-msg">Nenhum evento criado.</p>
            <?php endif; ?>
        </div>
        <div class="event-section">
            <h3 class="section-title title-participating"><i class="fa-solid fa-ticket"></i> Eventos que Vou</h3>
            <?php if (count($eventosParticipa) > 0): ?>
                <div class="cards-grid">
                    <?php foreach($eventosParticipa as $evt): ?>
                        <div class="event-card card-participating" onclick="verEvento(<?php echo $evt['idEvento']; ?>)">
                            <div class="card-date"><span class="day"><?php echo formatarData($evt['dia']); ?></span></div>
                            <div class="card-info">
                                <h4><?php echo htmlspecialchars($evt['nome']); ?></h4>
                                <span class="time"><i class="fa-regular fa-clock"></i> <?php echo substr($evt['horario_inicio'], 0, 5); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="empty-msg">Não está participando de eventos.</p>
            <?php endif; ?>
        </div>
    </div>
  </main>

  <nav class="bottombar">
        <a href="<?php echo $url_base; ?>TelaPerfils/perfil.php" class="nav-btn active">
            <i class="fa-solid fa-users"></i>
        </a>
        <a href="<?php echo $url_base; ?>tela-atividades/atividades.php" class="nav-btn">
            <i class="fa-solid fa-person-walking"></i>
        </a>
        <a href="<?php echo $url_base; ?>tela-principal/telaprincipal.php" class="nav-btn">
            <i class="fa-solid fa-house"></i>
        </a>
        <a href="../telas-mapas/index.html" class="nav-btn">
        <svg version="1.0" xmlns="http://www.w3.org/2000/svg" width="28px" height="28px" viewBox="0 0 512.000000 512.000000" preserveAspectRatio="xMidYMid meet">
            <g transform="translate(0.000000,512.000000) scale(0.100000,-0.100000)" fill="#333" stroke="none">
                <path d="M3880 5114 c-253 -46 -368 -91 -527 -203 -320 -226 -515 -647 -464 -1002 50 -348 378 -896 903 -1510 135 -159 185 -185 274 -143 54 26 304 324 523 624 372 510 549 910 528 1190 -40 510 -395 920 -892 1025 -77 17 -294 28 -345 19z m221 -648 c94 -20 164 -59 236 -132 188 -187 187 -481 -1 -670 -189 -189 -483 -189 -672 0 -188 189 -189 483 -1 670 121 122 271 167 438 132z"/>
                <path d="M836 3535 c-543 -218 -767 -312 -788 -332 -15 -15 -33 -44 -38 -64 -7 -24 -9 -566 -8 -1535 l3 -1499 24 -34 c26 -36 90 -70 133 -71 15 0 345 127 733 282 l705 282 0 1638 c0 901 -1 1638 -2 1638 -2 -1 -345 -138 -762 -305z"/>
                <path d="M1920 2202 l0 -1638 640 -256 640 -256 0 1285 0 1284 -69 98 c-185 261 -346 534 -431 731 l-41 95 -337 134 c-185 74 -352 141 -369 148 l-33 13 0 -1638z"/>
                <path d="M5076 3029 c-192 -313 -602 -845 -766 -993 -95 -86 -248 -129 -382 -108 -122 18 -201 65 -312 185 l-96 102 0 -1107 0 -1108 48 19 c26 10 370 148 764 306 791 316 770 305 783 397 3 24 4 567 3 1207 l-3 1164 -39 -64z"/>
            </g>
        </svg>
        </a>
        <a href="<?php echo $url_base; ?>teladeusuario.php" class="nav-btn">
            <i class="fa-solid fa-user"></i>
        </a>
    </nav>

  <script>
    function goBack() { window.location.href = '../TelaPerfils/perfil.php'; }
    function navegar(url) { window.location.href = url; }
    function verEvento(id) { window.location.href = '../tela-evento/tela-evento.php?id=' + id; }
    function toggleSeguirProfile(id) {
        const btn = document.getElementById('btn-follow-profile');
        if(!btn) return;
        const countEl = document.getElementById('count-seguidores');
        let current = parseInt(countEl.innerText);
        const isFollowing = btn.classList.contains('btn-seguindo');
        if (isFollowing) {
            btn.className = 'btn-action btn-seguir'; btn.innerText = 'Seguir'; countEl.innerText = Math.max(0, current - 1);
        } else {
            btn.className = 'btn-action btn-seguindo'; btn.innerText = 'Seguindo'; countEl.innerText = current + 1;
        }
        const fd = new FormData(); fd.append('id', id);
        fetch('../TelaPerfils/seguir.php', { method: 'POST', body: fd }).then(r => r.json()).then(d => { if(!d.success) location.reload(); });
    }
  </script>
</body>
</html>
