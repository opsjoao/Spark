<?php
require_once('../formulario-cadastro-login/verificacao.php');
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { die("Erro: ID de evento inválido."); }
$idEvento = $_GET['id'];

$servidor = "localhost"; $usuario_db = "root"; $senha_db = ""; $banco = "spark";
$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) { die("Falha na conexão: " . $conexao->connect_error); }

// Busca os detalhes do Evento
$stmt_evento = $conexao->prepare("SELECT e.*, p.nome AS nome_parque FROM Evento AS e JOIN Parque AS p ON e.idParque = p.idParque WHERE e.idEvento = ?");
$stmt_evento->bind_param("i", $idEvento);
$stmt_evento->execute();
$resultado_evento = $stmt_evento->get_result();
if ($resultado_evento->num_rows === 0) { die("Erro: Evento não encontrado."); }
$evento = $resultado_evento->fetch_assoc();
$idParqueDoEvento = $evento['idParque'];

// Busca as Avaliações do Parque
$stmt_avaliacoes = $conexao->prepare("SELECT av.*, u.nome, u.username, u.avatar_path FROM Avaliacao_parque AS av JOIN Usuario AS u ON av.idUsuario = u.idUsuario WHERE av.idParque = ? ORDER BY av.idAvaliacao DESC");
$stmt_avaliacoes->bind_param("i", $idParqueDoEvento);
$stmt_avaliacoes->execute();
$resultado_avaliacoes = $stmt_avaliacoes->get_result();

// Lógica para exibir mensagens de feedback
$feedback_message = '';
$feedback_class = '';
if (isset($_GET['status'])) {
    switch ($_GET['status']) {
        case 'confirmado':
            $feedback_message = 'Presença confirmada com sucesso!';
            $feedback_class = 'success';
            break;
        case 'ja_inscrito':
            $feedback_message = 'Você já está inscrito neste evento.';
            $feedback_class = 'info';
            break;
        case 'erro':
            $feedback_message = 'Ocorreu um erro ao confirmar sua presença.';
            $feedback_class = 'error';
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes: <?php echo htmlspecialchars($evento['nome']); ?></title>
    <link rel="stylesheet" href="tela-evento.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        .feedback { padding: 12px; border-radius: 8px; margin: 16px 0; text-align: center; font-weight: bold; }
        .feedback.success { background-color: #D4EDDA; color: #155724; }
        .feedback.info { background-color: #D1ECF1; color: #0C5460; }
        .feedback.error { background-color: #F8D7DA; color: #721C24; }
    </style>
</head>
<body>
    <header class="topbar">
        <a href="javascript:history.back()" class="back-button"><i class="fa-solid fa-chevron-left"></i></a>
        <h1>Detalhes do Evento</h1>
    </header>
    <main>
        <div class="event-container">
            <?php if (!empty($evento['imagem_path'])): ?>
                <img src="/Spark-main/<?php echo htmlspecialchars($evento['imagem_path']); ?>" alt="Imagem do Evento" class="event-image-header">
            <?php endif; ?>
            <div class="event-info">
                <h1 class="event-title"><?php echo htmlspecialchars($evento['nome']); ?></h1>
                <p class="event-park"><?php echo htmlspecialchars($evento['nome_parque']); ?></p>
                <ul class="event-details">
                    <li><i class="fa-regular fa-calendar"></i><span>Data: <?php echo date("d/m/Y", strtotime($evento['dia'])); ?></span></li>
                    <li><i class="fa-regular fa-clock"></i><span>Horário de Início: <?php echo date("H:i", strtotime($evento['horario_inicio'])); ?></span></li>
                    <li><i class="fa-solid fa-clock"></i><span>Horário de Término: <?php echo date("H:i", strtotime($evento['horario_termino'])); ?></span></li>
                </ul>
                <?php if (!empty($evento['descricao'])): ?>
                    <div class="event-description"><h2>Sobre o Evento</h2><p><?php echo nl2br(htmlspecialchars($evento['descricao'])); ?></p></div>
                <?php endif; ?>

                <?php if ($feedback_message): ?>
                    <div class="feedback <?php echo $feedback_class; ?>"><?php echo $feedback_message; ?></div>
                <?php endif; ?>
                
                <form action="confirmar_presenca.php" method="POST">
                    <input type="hidden" name="idEvento" value="<?php echo $evento['idEvento']; ?>">
                    <button type="submit" class="cta-button">Confirmar Presença</button>
                </form>
            </div>
        </div>
        <section class="reviews-section">
            <h2>Avaliações sobre o Parque</h2>
            <?php if ($resultado_avaliacoes->num_rows > 0): ?>
                <?php while($avaliacao = $resultado_avaliacoes->fetch_assoc()): 
                    $avatar_avaliacao = !empty($avaliacao['avatar_path']) ? $avaliacao['avatar_path'] : 'assets/images/avatar_padrao.png';
                ?>
                <div class="review-card">
                    <img src="/Spark-main/<?php echo htmlspecialchars($avatar_avaliacao); ?>" alt="avatar" class="avatar">
                    <div class="review-content">
                        <div class="review-header"><h3><?php echo htmlspecialchars($avaliacao['nome']); ?></h3><span>@<?php echo htmlspecialchars($avaliacao['username']); ?></span></div>
                        <div class="stars"><?php for ($i = 1; $i <= 5; $i++) { echo ($i <= $avaliacao['nota']) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>'; } ?></div>
                        <p><?php echo htmlspecialchars($avaliacao['comentario']); ?></p>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-reviews">Este parque ainda não possui avaliações.</p>
            <?php endif; ?>
        </section>
    </main>
    <nav class="bottombar">
        <button class="nav-btn" onclick="window.location.href='/Spark-main/TelaPerfils/perfil.html'"><i class="fa-solid fa-users"></i><span>Amigos</span></button>
        <button class="nav-btn active"><i class="fa-solid fa-person-walking"></i><span>Atividades</span></button>
        <button class="nav-btn" onclick="window.location.href='/Spark-main/tela-principal/telaprincipal.php'"><i class="fa-solid fa-house"></i><span>Início</span></button>
        <button class="nav-btn"><i class="fa-solid fa-star"></i><span>Favoritos</span></button>
        <button class="nav-btn" onclick="window.location.href='/Spark-main/teladeusuario/teladeusuario.php'"><i class="fa-solid fa-user"></i><span>Conta</span></button>
    </nav>
</body>
</html>
<?php
$stmt_evento->close();
$stmt_avaliacoes->close();
$conexao->close();
?>
