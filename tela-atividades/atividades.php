<?php
// --- INÍCIO DA SUBSTITUIÇÃO ---
// Inclui o nosso "guardião". Ele já faz o session_start() e a verificação de login.
// Certifique-se de que o caminho relativo para o 'verificacao.php' está correto.
require_once('../formulario-cadastro-login/verificacao.php');

// Pega o ID do usuário da sessão (agora temos certeza que ele existe)
$idUsuarioLogado = $_SESSION['id_usuario'];

// 1. Conexão com o Banco de Dados
$servidor = "localhost";
$usuario_db = "root"; // Renomeado para não conflitar com a sessão
$senha_db = "";
$banco = "spark";

$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// 2. Consulta SQL para buscar PRÓXIMOS EVENTOS (eventos futuros)
$sql_proximos = "
    SELECT 
        e.idEvento, e.nome AS nome_evento, e.descricao, e.dia, e.horario_inicio, e.imagem_path,
        p.nome AS nome_parque,
        u.nome AS nome_usuario, u.username, u.avatar_path
    FROM Evento AS e
    JOIN Parque AS p ON e.idParque = p.idParque
    JOIN Usuario AS u ON e.idUsuario = u.idUsuario
    WHERE u.status = 'ativo' AND e.dia >= CURDATE()
    ORDER BY e.dia, e.horario_inicio;
";
$resultado_proximos = $conexao->query($sql_proximos);

// 3. Consulta SQL para buscar o HISTÓRICO (eventos que o usuário participou)
$sql_historico = "
    SELECT 
        e.idEvento, e.nome AS nome_evento, e.descricao, e.dia, e.horario_inicio, e.imagem_path,
        p.nome AS nome_parque,
        u.nome AS nome_usuario, u.username, u.avatar_path
    FROM Evento AS e
    JOIN Parque AS p ON e.idParque = p.idParque
    JOIN Usuario AS u ON e.idUsuario = u.idUsuario
    JOIN Participantes AS pa ON e.idEvento = pa.idEvento
    WHERE pa.idUsuario = ? AND e.dia < CURDATE()
    ORDER BY e.dia DESC, e.horario_inicio DESC;
";
$stmt_historico = $conexao->prepare($sql_historico);
$stmt_historico->bind_param("i", $idUsuarioLogado);
$stmt_historico->execute();
$resultado_historico = $stmt_historico->get_result();

// --- FIM DA SUBSTITUIÇÃO ---
// O restante do arquivo (o HTML) permanece o mesmo.
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atividades - Spark</title>
    
    <link rel="stylesheet" href="atividades.css">
    
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <header class="topbar">
        <i class='bx bx-menu'></i>
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
            <button class="tab-button" onclick="showTab('historico')">Histórico</button>
        </div>

        <div id="proximos" class="tab-content active">
            <?php
            if ($resultado_proximos && $resultado_proximos->num_rows > 0) {
                while($evento = $resultado_proximos->fetch_assoc()) {
                    $caminho_avatar_padrao = 'assets/images/avatar_padrao.png';
                    $avatar = !empty($evento['avatar_path']) ? $evento['avatar_path'] : $caminho_avatar_padrao;
            ?>
            <a href="/Spark-main/tela-evento/tela-evento.php?id=<?php echo $evento['idEvento']; ?>" class="post-link">
                <section class="post">
                    <div class="post-header">
                        <img src="/Spark-main/<?php echo htmlspecialchars($avatar); ?>" alt="Foto de perfil do usuário" class="avatar">
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($evento['nome_usuario']); ?></h3>
                            <p>@<?php echo htmlspecialchars($evento['username']); ?></p>
                        </div>
                    </div>

                    <?php if (!empty($evento['imagem_path'])): ?>
                        <img src="/Spark-main/formulario cadastro eventos/<?php echo htmlspecialchars($evento['imagem_path']); ?>" alt="Imagem do Evento" class="post-img">
                    <?php endif; ?>
                    
                    <div class="post-body">
                        <div class="post-title-line">
                            <div class="title-and-location">
                                <h4><?php echo htmlspecialchars($evento['nome_evento']); ?></h4>
                                <p class="local"><?php echo htmlspecialchars($evento['nome_parque']); ?></p>
                            </div>
                            <div class="activity-date">
                                <i class="fa-solid fa-calendar-week"></i>
                                <span>
                                    <?php echo date("d/m", strtotime($evento['dia'])) . ", " . date("H:i", strtotime($evento['horario_inicio'])); ?>
                                </span>
                            </div>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($evento['descricao'])); ?></p>
                    </div>
                </section>
            </a>
            <?php
                } // Fim do while
            } else {
                echo "<p class='empty-message'>Nenhum próximo evento encontrado.</p>";
            }
            ?>
        </div>

        <div id="historico" class="tab-content">
            <?php
            if ($resultado_historico && $resultado_historico->num_rows > 0) {
                while($evento = $resultado_historico->fetch_assoc()) {
                    $caminho_avatar_padrao = 'assets/images/avatar_padrao.png';
                    $avatar = !empty($evento['avatar_path']) ? $evento['avatar_path'] : $caminho_avatar_padrao;
            ?>
            <a href="/Spark-main/tela-evento/tela-evento.php?id=<?php echo $evento['idEvento']; ?>" class="post-link">
                <section class="post">
                    <div class="post-header">
                        <img src="/Spark-main/<?php echo htmlspecialchars($avatar); ?>" alt="Foto de perfil do usuário" class="avatar">
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($evento['nome_usuario']); ?></h3>
                            <p>@<?php echo htmlspecialchars($evento['username']); ?></p>
                        </div>
                    </div>

                    <?php if (!empty($evento['imagem_path'])): ?>
                        <img src="/Spark-main/formulario cadastro eventos/<?php echo htmlspecialchars($evento['imagem_path']); ?>" alt="Imagem do Evento" class="post-img">
                    <?php endif; ?>
                    
                    <div class="post-body">
                        <div class="post-title-line">
                            <div class="title-and-location">
                                <h4><?php echo htmlspecialchars($evento['nome_evento']); ?></h4>
                                <p class="local"><?php echo htmlspecialchars($evento['nome_parque']); ?></p>
                            </div>
                            <div class="activity-date">
                                <i class="fa-solid fa-calendar-week"></i>
                                <span>
                                    <?php echo date("d/m/Y", strtotime($evento['dia'])); ?>
                                </span>
                            </div>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($evento['descricao'])); ?></p>
                    </div>
                </section>
            </a>
            <?php
                } // Fim do while
            } else {
                echo "<p class='empty-message'>Você ainda não participou de nenhum evento.</p>";
            }
            ?>
        </div>
    </main>
    
    <a href="/Spark-main/formulario cadastro eventos/criar_evento.html" class="fab">
        <i class="fa-solid fa-calendar-plus"></i>
    </a>

    <nav class="bottombar">
        <button class="nav-btn" onclick="window.location.href='/Spark-main/TelaPerfils/perfil.html'">
            <i class="fa-solid fa-users"></i>
            <span>Amigos</span>
        </button>
        <button class="nav-btn active">
            <i class="fa-solid fa-person-walking"></i>
            <span>Atividades</span>
        </button>
        <button class="nav-btn" onclick="window.location.href='/Spark-main/tela-principal/telaprincipal.php'">
            <i class="fa-solid fa-house"></i>
            <span>Início</span>
        </button>
        <button class="nav-btn">
            <i class="fa-solid fa-star"></i>
            <span>Favoritos</span>
        </button>
        <button class="nav-btn" onclick="window.location.href='/Spark-main/teladeusuario/teladeusuario.php'">
            <i class="fa-solid fa-user"></i>
            <span>Conta</span>
        </button>
    </nav>

    <script>
        function showTab(tabName) {
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));

            const tabButtons = document.querySelectorAll('.tab-button');
            tabButtons.forEach(button => button.classList.remove('active'));

            document.getElementById(tabName).classList.add('active');
            document.querySelector(`.tab-button[onclick="showTab('${tabName}')"]`).classList.add('active');
        }
    </script>
</body>
</html>
<?php
$stmt_historico->close();
$conexao->close();
?>
