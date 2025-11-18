<?php
// Define o fuso horário para São Paulo, garantindo que o tempo seja consistente
date_default_timezone_set('America/Sao_Paulo');

// Inclui o guardião de sessão
require_once('../formulario-cadastro-login/verificacao.php');
$idUsuarioLogado = $_SESSION['id_usuario'];

// Conexão com o Banco de Dados
$servidor = "localhost";
$usuario_db = "root";
$senha_db = "";
$banco = "spark";

$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// Pega a data e hora atuais no formato do SQL, usando o fuso horário correto
$agora_sql = date('Y-m-d H:i:s');

// 1. Consulta para PRÓXIMOS EVENTOS (ALTERADA: Apenas eventos inscritos)
$sql_proximos = "
    SELECT e.idEvento, e.nome AS nome_evento, e.descricao, e.dia, e.horario_inicio, e.imagem_path, 
           p.nome AS nome_parque, u.nome AS nome_usuario, u.username, u.avatar_path
    FROM Evento AS e
    JOIN Parque AS p ON e.idParque = p.idParque 
    JOIN Usuario AS u ON e.idUsuario = u.idUsuario
    /* JOIN adicionado para filtrar apenas onde o usuário é participante */
    INNER JOIN Participantes AS pa ON e.idEvento = pa.idEvento
    WHERE pa.idUsuario = ? 
      AND pa.status IN ('inscrito', 'ativo')
      AND u.status = 'ativo' 
      AND STR_TO_DATE(CONCAT(e.dia, ' ', e.horario_inicio), '%Y-%m-%d %H:%i:%s') >= ?
    ORDER BY e.dia, e.horario_inicio;
";

$stmt_proximos = $conexao->prepare($sql_proximos);

// ATENÇÃO: O bind_param mudou de "s" para "is" porque agora passamos o ID (inteiro) e a Data (string)
$stmt_proximos->bind_param("is", $idUsuarioLogado, $agora_sql);

$stmt_proximos->execute();
$resultado_proximos = $stmt_proximos->get_result();

// =================================================================================
// 2. CONSULTA PARA MEUS EVENTOS (ALTERADO: Apenas eventos criados pelo usuário)
// =================================================================================
$sql_meus_eventos = "
    SELECT e.idEvento, e.nome AS nome_evento, e.descricao, e.dia, e.horario_inicio, e.imagem_path, 
           p.nome AS nome_parque, u.nome AS nome_usuario, u.username, u.avatar_path
    FROM Evento AS e
    JOIN Parque AS p ON e.idParque = p.idParque 
    JOIN Usuario AS u ON e.idUsuario = u.idUsuario 
    WHERE 
        e.idUsuario = ? -- Filtra apenas onde o ID do criador é o seu
        AND STR_TO_DATE(CONCAT(e.dia, ' ', e.horario_termino), '%Y-%m-%d %H:%i:%s') >= ?
    ORDER BY e.dia, e.horario_inicio;
";

$stmt_meus_eventos = $conexao->prepare($sql_meus_eventos);

// ATENÇÃO: Agora passamos apenas UM inteiro (seu ID) e uma string (Data)
// O bind mudou de "iis" para "is"
$stmt_meus_eventos->bind_param("is", $idUsuarioLogado, $agora_sql);

$stmt_meus_eventos->execute();
$resultado_meus_eventos = $stmt_meus_eventos->get_result();
// =================================================================================

// 3. Consulta para HISTÓRICO (sem alterações)
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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atividades - Spark</title>
    
    <link rel="stylesheet" href="atividades.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<header class="topbar">
    <i class='bx bx-search' style="visibility: hidden;"></i>
    
    <h1>Atividades</h1>
    <i class='bx bx-search'></i>
</header>
    <nav class="sidebar">
        <a href="/Spark-main/tela-principal/telaprincipal.php"><i class='bx bx-home'></i><span>Início</span></a>
        <a href="/Spark-main/tela-atividades/atividades.php" class="active"><i class='bx bx-run'></i><span>Atividades</span></a>
        <a href="/Spark-main/TelaPerfils/perfil.html"><i class='bx bx-user'></i><span>Amigos</span></a>
        <a href="#"><i class='bx bx-star'></i><span>Favoritos</span></a>
        <a href="/Spark-main/teladeusuario/teladeusuario.php"><i class='bx bx-cog'></i><span>Conta</span></a>
    </nav>

    <main>
        <div class="tabs-container">
            <button class="tab-button active" onclick="showTab('proximos')">Próximos Eventos</button>
            <button class="tab-button" onclick="showTab('meus-eventos')">Meus Eventos</button>
            <button class="tab-button" onclick="showTab('historico')">Histórico</button>
        </div>

        <?php
function renderEventCard($evento, $showYear = false) {
    // Configuração dos caminhos padrão
    $caminho_avatar_padrao = 'assets/images/avatar_padrao.png';
    $caminho_imagem_evento_padrao = 'uploads/eventos/default_event.jpg'; // <--- CRIE ESTA IMAGEM NA SUA PASTA!

    // Lógica do Avatar
    $avatar = !empty($evento['avatar_path']) ? $evento['avatar_path'] : $caminho_avatar_padrao;
    
    // Lógica da Data
    $dateFormat = $showYear ? "d/m/Y" : "d/m";

    // --- LÓGICA DA IMAGEM DO EVENTO ---
    // 1. Define qual caminho tentar usar: o do banco ou o padrão
    $img_path_banco = !empty($evento['imagem_path']) ? $evento['imagem_path'] : $caminho_imagem_evento_padrao;
    
    // 2. Monta as URLs completas para o HTML
    $url_imagem_final = '/Spark-main/' . htmlspecialchars($img_path_banco);
    $url_imagem_padrao = '/Spark-main/' . $caminho_imagem_evento_padrao;
    // ----------------------------------

    echo "<a href='/Spark-main/tela-evento/tela-evento.php?id={$evento['idEvento']}' class='post-link'>";
    echo "<section class='post'>";
    
    // Cabeçalho do Post (Usuário)
    echo "<div class='post-header'>";
    echo "<img src='/Spark-main/{$avatar}' alt='Foto de perfil' class='avatar'>";
    echo "<div class='user-info'><h3>" . htmlspecialchars($evento['nome_usuario']) . "</h3><p>@" . htmlspecialchars($evento['username']) . "</p></div>";
    echo "</div>";
    
    // Imagem do Evento com PROTEÇÃO DE ERRO (onerror)
    // Se a imagem principal falhar (404), o navegador troca automaticamente para a padrão
    echo "<img src='$url_imagem_final' 
               alt='Imagem do Evento' 
               class='post-img' 
               onerror=\"this.onerror=null;this.src='$url_imagem_padrao';\">";

    // Corpo do Post (Título, Local, Descrição)
    echo "<div class='post-body'>";
    echo "<div class='post-title-line'>";
    echo "<div class='title-and-location'><h4>" . htmlspecialchars($evento['nome_evento']) . "</h4><p class='local'>" . htmlspecialchars($evento['nome_parque']) . "</p></div>";
    echo "<div class='activity-date'><i class='fa-solid fa-calendar-week'></i><span>" . date($dateFormat, strtotime($evento['dia'])) . ", " . date("H:i", strtotime($evento['horario_inicio'])) . "</span></div>";
    echo "</div>";
    echo "<p>" . nl2br(htmlspecialchars($evento['descricao'])) . "</p>";
    echo "</div>";
    
    echo "</section></a>";
}
?>

<div id="proximos" class="tab-content active">
            <?php
            if ($resultado_proximos && $resultado_proximos->num_rows > 0) {
                while($evento = $resultado_proximos->fetch_assoc()) {
                    renderEventCard($evento, false);
                }
            } else {
                echo "<p class='empty-message'>Nenhum próximo evento encontrado.</p>";
            }
            ?>
        </div>

        <div id="meus-eventos" class="tab-content">
            <?php
            if ($resultado_meus_eventos && $resultado_meus_eventos->num_rows > 0) {
                while($evento = $resultado_meus_eventos->fetch_assoc()) {
                    renderEventCard($evento, false);
                }
            } else {
                echo "<p class='empty-message'>Você ainda não se inscreveu em nenhum evento futuro.</p>";
            }
            ?>
            <a href="/Spark-main/formulario cadastro eventos/criar_evento.html" class="fab">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" ><path fill="rgb(255,255,255)" d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z"/></svg>
            </a>
        </div>

        <div id="historico" class="tab-content">
            <?php
            if ($resultado_historico && $resultado_historico->num_rows > 0) {
                while($evento = $resultado_historico->fetch_assoc()) {
                    renderEventCard($evento, true); // Mostra o ano no histórico
                }
            } else {
                echo "<p class='empty-message'>Seu histórico de eventos está vazio.</p>";
            }
            ?>
        </div>
    </main>
    
    <nav class="bottombar">
    <button class="nav-btn" onclick="window.location.href='../TelaPerfils/perfil.html'">
      <i class="fa-solid fa-users"></i>
    </button>
    <button class="nav-btn active" onclick="window.location.href='../tela-atividades/atividades.php'">
      <i class="fa-solid fa-person-walking"></i>
    </button>
    <button class="nav-btn" onclick="window.location.href='../tela-principal/telaprincipal.php'">
      <i class="fa-solid fa-house"></i>
    </button>
    <button class="nav-btn" onclick="window.location.href='../telaFavoritos/index.html'">
      <i class="fa-solid fa-star"></i>
    </button>
    <button class="nav-btn" onclick="window.location.href='../teladeusuario/teladeusuario.php'">
      <i class="fa-solid fa-user"></i>
    </button>
  </nav>

    <script>
    // Função para mostrar a aba (você já a tem)
    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        document.querySelector(`.tab-button[onclick="showTab('${tabName}')"]`).classList.add('active');
    }

    // NOVO: Lógica para ativar a aba correta ao carregar a página
    document.addEventListener('DOMContentLoaded', function() {
        // Pega os parâmetros da URL
        const params = new URLSearchParams(window.location.search);
        const abaParaAtivar = params.get('aba');

        // Se a URL tiver o parâmetro 'aba' (ex: ?aba=meus-eventos)
        if (abaParaAtivar) {
            // Chama a função para mostrar a aba especificada
            showTab(abaParaAtivar);
        }
    });
</script>

</body>
</html>
<?php
$stmt_historico->close();
$stmt_meus_eventos->close();
$conexao->close();
?>
