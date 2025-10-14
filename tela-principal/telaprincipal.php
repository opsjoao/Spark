<?php
// Inclui o guardião de sessão
require_once('../formulario-cadastro-login/verificacao.php');

// Conexão com o Banco de Dados
$servidor = "localhost"; $usuario_db = "root"; $senha_db = ""; $banco = "Spark";
$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) { die("Falha na conexão: " . $conexao->connect_error); }

// --- CONSULTA PARA ATIVIDADES EM DESTAQUE (Carrossel) ---
$sql_destaques = "
    SELECT idEvento, nome, imagem_path FROM Evento
    WHERE imagem_path IS NOT NULL AND STR_TO_DATE(CONCAT(dia, ' ', horario_inicio), '%Y-%m-%d %H:%i:%s') >= NOW()
    ORDER BY dia ASC, horario_inicio ASC LIMIT 5;
";
$resultado_destaques = $conexao->query($sql_destaques);

// --- CONSULTA PARA O FEED (CORRIGIDA PARA PEGAR O ID DA AVALIAÇÃO) ---
$sql_feed = "
    SELECT 
        e.idEvento, e.nome AS nome_evento, e.imagem_path AS imagem_evento,
        p.nome AS nome_parque,
        criador.nome AS nome_criador, criador.username AS username_criador, criador.avatar_path AS avatar_criador,
        av.idAvaliacao, av.nota AS nota_avaliacao, av.comentario AS comentario_avaliacao, av.data_avaliacao,
        avaliador.nome AS nome_avaliador
    FROM Evento AS e
    JOIN Parque AS p ON e.idParque = p.idParque
    JOIN Usuario AS criador ON e.idUsuario = criador.idUsuario
    JOIN (
        -- Seleciona a avaliação mais recente de cada evento
        SELECT idAvaliacao, idEvento, nota, comentario, idUsuario, data_avaliacao, ROW_NUMBER() OVER(PARTITION BY idEvento ORDER BY data_avaliacao DESC) as rn
        FROM Avaliacao_evento
    ) AS av ON e.idEvento = av.idEvento AND av.rn = 1
    JOIN Usuario AS avaliador ON av.idUsuario = avaliador.idUsuario
    ORDER BY av.data_avaliacao DESC
    LIMIT 10;
";
$resultado_feed = $conexao->query($sql_feed);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spark</title>
    <link rel="stylesheet" href="telaprincipal.css">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <header class="topbar">
        <i class='bx bx-menu'></i>
        <h1>Procure por um parque</h1>
        <i class='bx bx-search' onclick="window.location.href='../telas-mapas/index.html'"></i>
    </header>

    <nav class="sidebar">
        <a href="/Spark-main/tela-principal/telaprincipal.php" class="active"><i class='bx bx-home'></i><span>Início</span></a>
        <a href="../tela-atividades/atividades.php"><i class='bx bx-run'></i><span>Atividades</span></a>
        <a href="../TelaPerfils/perfil.html"><i class='bx bx-user'></i><span>Amigos</span></a>
        <a href="#"><i class='bx bx-star'></i><span>Favoritos</span></a>
        <a href="../teladeusuario/teladeusuario.php"><i class='bx bx-cog'></i><span>Conta</span></a>
    </nav>

    <main>
        <section class="destaque">
            <h2>Atividades em Destaque</h2>
            <div class="carousel-container">
                <?php
                if ($resultado_destaques && $resultado_destaques->num_rows > 0) {
                    while($evento = $resultado_destaques->fetch_assoc()) {
                        echo "<a href='/Spark-main/tela-evento/tela-evento.php?id={$evento['idEvento']}' class='carousel-item'>";
                        echo "<img src='/Spark-main/{$evento['imagem_path']}' alt='Imagem do evento {$evento['nome']}'>";
                        echo "<div class='carousel-item-text'>{$evento['nome']}</div>";
                        echo "</a>";
                    }
                } else {
                    echo "<p>Nenhuma atividade em destaque no momento.</p>";
                }
                ?>
            </div>
        </section>
        
        <?php
        if ($resultado_feed && $resultado_feed->num_rows > 0) {
            while($post = $resultado_feed->fetch_assoc()) {
                $avatar = !empty($post['avatar_criador']) ? $post['avatar_criador'] : 'assets/images/avatar_padrao.png';
        ?>
        <a href="/Spark-main/tela-evento/tela-evento.php?id=<?php echo $post['idEvento']; ?>&highlight=<?php echo $post['idAvaliacao']; ?>" class="post-link">
            <section class="post">
                <div class="post-header">
                    <img src="/Spark-main/<?php echo htmlspecialchars($avatar); ?>" alt="user" class="avatar">
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($post['nome_criador']); ?></h3>
                        <p>@<?php echo htmlspecialchars($post['username_criador']); ?></p>
                    </div>
                    <div class="post-menu"></div>
                </div>
                
                <?php if (!empty($post['imagem_evento'])): ?>
                    <img src="/Spark-main/<?php echo htmlspecialchars($post['imagem_evento']); ?>" alt="Imagem do Evento" class="post-img">
                <?php endif; ?>
                
                <div class="post-body">
                    <div class="post-title-line">
                        <div class="title-and-location">
                            <h4><?php echo htmlspecialchars($post['nome_evento']); ?></h4>
                            <p class="local"><?php echo htmlspecialchars($post['nome_parque']); ?></p>
                        </div>
                        <?php if (!empty($post['nota_avaliacao'])): ?>
                            <p class="stars">
                                <?php for ($i = 1; $i <= 5; $i++) { echo ($i <= $post['nota_avaliacao']) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>'; } ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <p>
                        <strong><?php echo htmlspecialchars($post['nome_avaliador']); ?>:</strong>
                        <?php echo htmlspecialchars($post['comentario_avaliacao']); ?>
                    </p>
                </div>
            </section>
        </a>
        <?php
            } // Fim do while
        } else {
            echo "<p style='text-align: center; color: #555; padding: 20px;'>Nenhum evento avaliado para mostrar no feed.</p>";
        }
        ?>
    </main>

    <nav class="bottombar">
        <button class="nav-btn" onclick="window.location.href='../TelaPerfils/perfil.html'"><i class="fa-solid fa-users"></i><span></span></button>
        <button class="nav-btn" onclick="window.location.href='../tela-atividades/atividades.php'"><i class="fa-solid fa-person-walking"></i><span></span></button>
        <button class="nav-btn active" onclick="window.location.href='/Spark-main/tela-principal/telaprincipal.php'"><i class="fa-solid fa-house"></i><span></span></button>
        <button class="nav-btn" onclick="window.location.href='../telaFavoritos/index.html'"><i class="fa-solid fa-star"></i><span></span></button>
        <button class="nav-btn" onclick="window.location.href='../teladeusuario/teladeusuario.php'"><i class="fa-solid fa-user"></i><span></span></button>
    </nav>

    <script src="telaprincipal.js"></script>
</body>
</html>
<?php
$conexao->close();
?>