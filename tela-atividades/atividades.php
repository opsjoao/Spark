<?php
// Define o fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Inclui o guardião de sessão
require_once('../formulario-cadastro-login/verificacao.php');
$idUsuarioLogado = $_SESSION['id_usuario'];

// --- CORREÇÃO: Definindo a URL Base Globalmente ---
$url_base = '/Spark-main/';

// Conexão com o Banco de Dados
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "Spark";

$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}
$conexao->set_charset("utf8mb4");

$agora_sql = date('Y-m-d H:i:s');

// --- CONSULTAS SQL ---
// 1. Próximos
$sql_proximos = "
    SELECT e.idEvento, e.nome AS nome_evento, e.descricao, e.dia, e.horario_inicio, e.imagem_path, 
           p.nome AS nome_parque, u.nome AS nome_usuario, u.username, u.avatar_path
    FROM Evento AS e
    JOIN Parque AS p ON e.idParque = p.idParque 
    JOIN Usuario AS u ON e.idUsuario = u.idUsuario
    INNER JOIN Participantes AS pa ON e.idEvento = pa.idEvento
    WHERE pa.idUsuario = ? 
      AND pa.status IN ('inscrito', 'ativo')
      AND u.status = 'ativo' 
      AND STR_TO_DATE(CONCAT(e.dia, ' ', e.horario_inicio), '%Y-%m-%d %H:%i:%s') >= ?
    ORDER BY e.dia, e.horario_inicio;
";
$stmt_proximos = $conexao->prepare($sql_proximos);
$stmt_proximos->bind_param("is", $idUsuarioLogado, $agora_sql);
$stmt_proximos->execute();
$resultado_proximos = $stmt_proximos->get_result();

// 2. Meus Eventos
$sql_meus_eventos = "
    SELECT e.idEvento, e.nome AS nome_evento, e.descricao, e.dia, e.horario_inicio, e.imagem_path, 
           p.nome AS nome_parque, u.nome AS nome_usuario, u.username, u.avatar_path
    FROM Evento AS e
    JOIN Parque AS p ON e.idParque = p.idParque 
    JOIN Usuario AS u ON e.idUsuario = u.idUsuario 
    WHERE e.idUsuario = ? 
      AND STR_TO_DATE(CONCAT(e.dia, ' ', e.horario_termino), '%Y-%m-%d %H:%i:%s') >= ?
    ORDER BY e.dia, e.horario_inicio;
";
$stmt_meus_eventos = $conexao->prepare($sql_meus_eventos);
$stmt_meus_eventos->bind_param("is", $idUsuarioLogado, $agora_sql);
$stmt_meus_eventos->execute();
$resultado_meus_eventos = $stmt_meus_eventos->get_result();

// 3. Histórico
$sql_historico = "
    SELECT e.idEvento, e.nome AS nome_evento, e.descricao, e.dia, e.horario_inicio, e.imagem_path, p.nome AS nome_parque, u.nome AS nome_usuario, u.username, u.avatar_path
    FROM Evento AS e
    JOIN Parque AS p ON e.idParque = p.idParque 
    JOIN Usuario AS u ON e.idUsuario = u.idUsuario 
    JOIN Participantes AS pa ON e.idEvento = pa.idEvento
    WHERE pa.idUsuario = ? AND (pa.status = 'participou' OR STR_TO_DATE(CONCAT(e.dia, ' ', e.horario_termino), '%Y-%m-%d %H:%i:%s') < ?)
    ORDER BY e.dia DESC, e.horario_inicio DESC;
";
$stmt_historico = $conexao->prepare($sql_historico);
$stmt_historico->bind_param("is", $idUsuarioLogado, $agora_sql);
$stmt_historico->execute();
$resultado_historico = $stmt_historico->get_result();

// --- FUNÇÃO RENDER CARD ---
function renderEventCard($evento, $showYear = false) {
    global $url_base; // Usa a variável global
    
    $caminho_avatar_padrao = $url_base . 'uploads/avatars/default_avatar.jpg';
    $caminho_evento_padrao = $url_base . 'uploads/eventos/default_event.jpg';

    $avatar = (!empty($evento['avatar_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $url_base . $evento['avatar_path'])) ? $url_base . $evento['avatar_path'] : $caminho_avatar_padrao;
    $img_final = (!empty($evento['imagem_path']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $url_base . $evento['imagem_path'])) ? $url_base . $evento['imagem_path'] : $caminho_evento_padrao;

    $dateFormat = $showYear ? "d/m/Y" : "d/m";
    $data_formatada = date($dateFormat, strtotime($evento['dia']));
    $hora_formatada = date("H:i", strtotime($evento['horario_inicio']));

    echo "<a href='/Spark-main/tela-evento/tela-evento.php?id={$evento['idEvento']}' class='post-link'>";
    echo "<section class='post'>";
    echo "<div class='post-header'><img src='{$avatar}' class='avatar'><div class='user-info'><h3>" . htmlspecialchars($evento['nome_usuario']) . "</h3><p>@" . htmlspecialchars($evento['username']) . "</p></div></div>";
    echo "<img src='$img_final' class='post-img'>";
    echo "<div class='post-body'>";
    echo "<div class='post-row-title'><h4>" . htmlspecialchars($evento['nome_evento']) . "</h4><div class='activity-date'><i class='far fa-calendar-alt'></i> " . $data_formatada . ", " . $hora_formatada . "</div></div>";
    echo "<p class='local'>" . htmlspecialchars($evento['nome_parque']) . "</p>";
    echo "<p class='description'>" . nl2br(htmlspecialchars($evento['descricao'])) . "</p>";
    echo "</div></section></a>";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atividades - Spark</title>
    <link rel="stylesheet" href="<?php echo $url_base; ?>style.css"> 
    <link rel="stylesheet" href="atividades.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>

    <!-- CABEÇALHO FIXO -->
    <header class="activity-header">
        <div class="header-content-wrapper">
            
            <!-- Barra Superior -->
            <div class="header-top-row">
                <div class="header-title-container">
                    <h1>Atividades</h1>
                    <i class="fas fa-search search-icon"></i>
                </div>
            </div>
            
            <!-- Abas -->
            <div class="tabs-container" id="tabs-nav">
                <button class="tab-button active" onclick="showTab('proximos', this)">Próximos<br>Eventos</button>
                <button class="tab-button" onclick="showTab('meus-eventos', this)">Meus<br>Eventos</button>
                <button class="tab-button" onclick="showTab('historico', this)">Histórico</button>
                <div class="active-tab-indicator"></div>
            </div>
        </div>
    </header>

    <main class="app-content">
        
        <div id="proximos" class="tab-content active">
            <?php if ($resultado_proximos && $resultado_proximos->num_rows > 0) { while($e = $resultado_proximos->fetch_assoc()) renderEventCard($e); } else { echo "<p class='empty-message'>Nenhuma inscrição futura.</p>"; } ?>
        </div>

        <div id="meus-eventos" class="tab-content">
            <?php if ($resultado_meus_eventos && $resultado_meus_eventos->num_rows > 0) { while($e = $resultado_meus_eventos->fetch_assoc()) renderEventCard($e); } else { echo "<p class='empty-message'>Nenhum evento criado.</p>"; } ?>
            
            <!-- BOTÃO FLUTUANTE -->
            <a href="/Spark-main/formulario cadastro eventos/criar_evento.html" class="fab">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" ><path fill="rgb(255,255,255)" d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z"/></svg>
            </a>
        </div>

        <div id="historico" class="tab-content">
            <?php if ($resultado_historico && $resultado_historico->num_rows > 0) { while($e = $resultado_historico->fetch_assoc()) renderEventCard($e, true); } else { echo "<p class='empty-message'>Histórico vazio.</p>"; } ?>
        </div>

    </main>
    
    <!-- NAVBAR -->
    <nav class="bottombar">
        <!-- ... (Navbar igual) ... -->
        <a href="<?php echo $url_base; ?>TelaPerfils/perfil.html" class="nav-btn">
            <i class="fa-solid fa-users"></i>
        </a>
        <a href="<?php echo $url_base; ?>tela-atividades/atividades.php" class="nav-btn">
            <i class="fa-solid fa-person-walking"></i>
        </a>
        <a href="<?php echo $url_base; ?>tela-principal/telaprincipal.php" class="nav-btn active">
            <i class="fa-solid fa-house"></i>
        </a>
        <a href="../telas-mapas/index.html" class="nav-btn">
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
        </a>
        <a href="<?php echo $url_base; ?>teladeusuario/teladeusuario.php" class="nav-btn">
            <i class="fa-solid fa-user"></i>
        </a>
    </nav>


    <script>
    function showTab(tabName, element) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
        const activeBtn = element || document.querySelector(`.tab-button[onclick*="${tabName}"]`);
        if(activeBtn) activeBtn.classList.add('active');
        moveIndicator(activeBtn);
    }

    function moveIndicator(activeBtn) {
        const indicator = document.querySelector('.active-tab-indicator');
        if(activeBtn && indicator) {
            const width = activeBtn.offsetWidth;
            const left = activeBtn.offsetLeft;
            indicator.style.width = `${width}px`;
            indicator.style.transform = `translateX(${left}px)`;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const params = new URLSearchParams(window.location.search);
        const abaParaAtivar = params.get('aba') || 'proximos';
        const startBtn = document.querySelector(`.tab-button[onclick*="${abaParaAtivar}"]`);
        showTab(abaParaAtivar, startBtn);
        window.addEventListener('resize', () => {
            const currentBtn = document.querySelector('.tab-button.active');
            moveIndicator(currentBtn);
        });
    });
    </script>

</body>
</html>
<?php
$stmt_historico->close();
$stmt_meus_eventos->close();
$stmt_proximos->close();
$conexao->close();
?>