<?php
// Define o fuso horário para todas as funções de data e hora
date_default_timezone_set('America/Sao_Paulo');

// Inclui o guardião de sessão
require_once('../formulario-cadastro-login/verificacao.php');
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) { die("Erro: ID de evento inválido."); }
$idEvento = $_GET['id'];
$idUsuarioLogado = $_SESSION['id_usuario'];

// Conexão com o Banco de Dados
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

// Busca os Participantes do evento
$stmt_participantes = $conexao->prepare("
    SELECT u.nome, u.username, u.avatar_path FROM Usuario AS u
    JOIN Participantes AS pa ON u.idUsuario = pa.idUsuario
    WHERE pa.idEvento = ? ORDER BY pa.dataInscricao ASC
");
$stmt_participantes->bind_param("i", $idEvento);
$stmt_participantes->execute();
$resultado_participantes = $stmt_participantes->get_result();

// Busca as Avaliações do Evento
$stmt_avaliacoes = $conexao->prepare("
    SELECT av.*, u.nome, u.username, u.avatar_path FROM Avaliacao_evento AS av 
    JOIN Usuario AS u ON av.idUsuario = u.idUsuario WHERE av.idEvento = ? 
    ORDER BY av.data_avaliacao DESC
");
$stmt_avaliacoes->bind_param("i", $idEvento);
$stmt_avaliacoes->execute();
$resultado_avaliacoes = $stmt_avaliacoes->get_result();

// Lógica para o botão dinâmico
$stmt_participacao = $conexao->prepare("SELECT status FROM Participantes WHERE idUsuario = ? AND idEvento = ?");
$stmt_participacao->bind_param("ii", $idUsuarioLogado, $idEvento);
$stmt_participacao->execute();
$participacao = $stmt_participacao->get_result()->fetch_assoc();
$status_participacao = $participacao['status'] ?? null;

$data_inicio_ts = strtotime($evento['dia'] . ' ' . $evento['horario_inicio']);
$data_termino_ts = strtotime($evento['dia'] . ' ' . $evento['horario_termino']);
$agora_ts = time();
$evento_esta_acontecendo = ($agora_ts >= $data_inicio_ts && $agora_ts < $data_termino_ts);
$evento_ja_terminou = $agora_ts >= $data_termino_ts;

$stmt_check_avaliacao = $conexao->prepare("SELECT idAvaliacao FROM Avaliacao_evento WHERE idUsuario = ? AND idEvento = ?");
$stmt_check_avaliacao->bind_param("ii", $idUsuarioLogado, $idEvento);
$stmt_check_avaliacao->execute();
$ja_avaliou = $stmt_check_avaliacao->get_result()->num_rows > 0;

$data_inicio_evento_js = date('c', $data_inicio_ts);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes: <?php echo htmlspecialchars($evento['nome']); ?></title>
    <link rel="stylesheet" href="tela-evento.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
    <header class="topbar">
        <a href="javascript:history.go(-1)" class="back-button"><i class="fa-solid fa-chevron-left"></i></a>
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
                    <li><i class="fa-regular fa-calendar"></i><div><strong>Data:</strong><span><?php echo date("d/m/Y", strtotime($evento['dia'])); ?></span></div></li>
                    <li><i class="fa-regular fa-clock"></i><div><strong>Horário de Início:</strong><span><?php echo date("H:i", strtotime($evento['horario_inicio'])); ?></span></div></li>
                    <li><i class="fa-solid fa-clock"></i><div><strong>Horário de Término:</strong><span><?php echo date("H:i", strtotime($evento['horario_termino'])); ?></span></div></li>
                </ul>
                <?php if (!empty($evento['descricao'])): ?>
                    <div class="event-description"><h2>Sobre o Evento</h2><p><?php echo nl2br(htmlspecialchars($evento['descricao'])); ?></p></div>
                <?php endif; ?>

                <div class="button-container">
                    <?php if ($status_participacao === 'participou' || $ja_avaliou): ?>
                        <button class="cta-button disabled" disabled>Evento Finalizado e Avaliado</button>
                    <?php elseif ($status_participacao === 'ativo'): ?>
                        <button class="cta-button participate" onclick="openEvaluationModal()">Finalizar Evento</button>
                    <?php elseif ($status_participacao === 'inscrito' && $evento_ja_terminou): ?>
                        <button class="cta-button participate" onclick="openEvaluationModal()">Participei! Avaliar agora</button>
                    <?php elseif ($status_participacao === 'inscrito' && $evento_esta_acontecendo): ?>
                        <button id="btnIniciarEvento" class="cta-button participate" onclick="iniciarEvento(<?php echo $idEvento; ?>)">Iniciar Evento</button>
                    <?php elseif ($status_participacao === 'inscrito'): ?>
                        <button id="btnIniciarEvento" class="cta-button" data-starttime-ms="<?php echo htmlspecialchars($data_inicio_ts * 1000); ?>" disabled>Carregando...</button>
                    <?php else: ?>
                        <form action="confirmar_presenca.php" method="POST" style="margin:0;"><input type="hidden" name="idEvento" value="<?php echo $evento['idEvento']; ?>"><button type="submit" class="cta-button">Confirmar Presença</button></form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <section class="participants-section">
            <h2>Participantes (<?php echo $resultado_participantes->num_rows; ?>)</h2>
            <?php if ($resultado_participantes->num_rows > 0): ?>
                <div class="participants-list">
                    <?php while($participante = $resultado_participantes->fetch_assoc()): 
                        $avatar_participante = !empty($participante['avatar_path']) ? $participante['avatar_path'] : 'assets/images/avatar_padrao.png';
                    ?>
                        <img src="/Spark-main/<?php echo htmlspecialchars($avatar_participante); ?>" 
                             title="<?php echo htmlspecialchars($participante['nome']); ?> (@<?php echo htmlspecialchars($participante['username']); ?>)"
                             class="participant-avatar">
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="no-participants">Seja o primeiro a confirmar presença!</p>
            <?php endif; ?>
        </section>

        <section class="reviews-section">
            <h2>Avaliações sobre o Evento</h2>
            <?php if ($resultado_avaliacoes && $resultado_avaliacoes->num_rows > 0): ?>
                <?php while($avaliacao = $resultado_avaliacoes->fetch_assoc()): 
                    $avatar_avaliacao = !empty($avaliacao['avatar_path']) ? $avaliacao['avatar_path'] : 'assets/images/avatar_padrao.png';
                ?>
                <div class="review-card">
                    <img src="/Spark-main/<?php echo htmlspecialchars($avatar_avaliacao); ?>" alt="avatar" class="avatar">
                    <div class="review-content">
                        <div class="review-header"><h3><?php echo htmlspecialchars($avaliacao['nome']); ?></h3><span>@<?php echo htmlspecialchars($avaliacao['username']); ?></span></div>
                        <div class="stars"><?php for ($i = 1; $i <= 5; $i++) { echo ($i <= $avaliacao['nota']) ? '<i class="fa-solid fa-star"></i>' : '<i class="fa-regular fa-star"></i>'; } ?></div>
                        <p><?php echo htmlspecialchars($avaliacao['comentario']); ?></p>
                        <?php if (!empty($avaliacao['imagem_path'])): ?>
                            <img src="/Spark-main/<?php echo htmlspecialchars($avaliacao['imagem_path']); ?>" alt="Imagem da avaliação" class="review-image">
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="no-reviews">Este evento ainda não possui avaliações.</p>
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

    <div id="evaluation-modal" class="modal-overlay">
        <div class="modal-content">
            <button class="close-modal" onclick="closeEvaluationModal()">&times;</button>
            <h2>Avaliar Evento</h2>
            <p>Conte-nos como foi sua experiência no evento "<?php echo htmlspecialchars($evento['nome']); ?>"!</p>
            <form action="avaliar_evento.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="idEvento" value="<?php echo $evento['idEvento']; ?>">
                <div class="star-rating">
                    <input type="radio" id="star5" name="nota" value="5" required/><label for="star5"></label>
                    <input type="radio" id="star4" name="nota" value="4"/><label for="star4"></label>
                    <input type="radio" id="star3" name="nota" value="3"/><label for="star3"></label>
                    <input type="radio" id="star2" name="nota" value="2"/><label for="star2"></label>
                    <input type="radio" id="star1" name="nota" value="1"/><label for="star1"></label>
                </div>
                <textarea name="comentario" placeholder="Escreva seu comentário (opcional)..."></textarea>
                <label for="imagemAvaliacao" class="file-upload-label"><i class="fa-solid fa-camera"></i> Adicionar Foto (Opcional)</label>
                <input type="file" id="imagemAvaliacao" name="imagemAvaliacao" accept="image/*" style="display:none;">
                <div id="image-preview-container"></div>
                <button type="submit" class="cta-button">Enviar Avaliação</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('evaluation-modal');
        function openEvaluationModal() { if (modal) modal.style.display = 'flex'; }
        function closeEvaluationModal() { if (modal) modal.style.display = 'none'; }

        function iniciarEvento(idEvento) {
            const btn = document.getElementById('btnIniciarEvento');
            if (!btn) return;
            btn.textContent = 'Iniciando...';
            btn.disabled = true;
            const formData = new FormData();
            formData.append('idEvento', idEvento);
            fetch('iniciar_evento.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.success) { window.location.reload(); } 
                else { alert('Erro: ' + (data.message || 'Não foi possível iniciar o evento.')); btn.textContent = 'Tente Novamente'; btn.disabled = false; }
            })
            .catch(error => console.error('Erro de conexão:', error));
        }

        document.addEventListener('DOMContentLoaded', function() {
            const btnIniciar = document.getElementById('btnIniciarEvento');
            if (btnIniciar && btnIniciar.hasAttribute('data-starttime-ms')) {
                const eventoStartTime = parseInt(btnIniciar.dataset.starttimeMs, 10);
                if (isNaN(eventoStartTime)) { btnIniciar.textContent = "Data inválida"; return; }
                const timerInterval = setInterval(function() {
                    const agora = new Date().getTime();
                    const distancia = eventoStartTime - agora;
                    if (distancia <= 0) {
                        clearInterval(timerInterval);
                        window.location.reload(); 
                    } else {
                        const dias = Math.floor(distancia / (1000 * 60 * 60 * 24));
                        const horas = Math.floor((distancia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                        const minutos = Math.floor((distancia % (1000 * 60 * 60)) / (1000 * 60));
                        const segundos = Math.floor((distancia % (1000 * 60)) / 1000);
                        let timerText = "Começa em: ";
                        if (dias > 0) timerText += dias + "d ";
                        timerText += horas.toString().padStart(2, '0') + ":" + minutos.toString().padStart(2, '0') + ":" + segundos.toString().padStart(2, '0');
                        btnIniciar.textContent = timerText;
                    }
                }, 1000);
            }

            const fileInput = document.getElementById('imagemAvaliacao');
            const previewContainer = document.getElementById('image-preview-container');
            if (fileInput && previewContainer) {
                fileInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            previewContainer.innerHTML = '';
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.classList.add('image-preview');
                            const removeBtn = document.createElement('button');
                            removeBtn.type = "button";
                            removeBtn.textContent = 'Remover Foto';
                            removeBtn.classList.add('remove-image-btn');
                            removeBtn.onclick = function() { fileInput.value = ''; previewContainer.innerHTML = ''; };
                            previewContainer.appendChild(img);
                            previewContainer.appendChild(removeBtn);
                        }
                        reader.readAsDataURL(file);
                    } else {
                        previewContainer.innerHTML = '';
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php
$stmt_evento->close();
$stmt_participantes->close();
$stmt_avaliacoes->close();
$stmt_participacao->close();
$stmt_check_avaliacao->close();
$conexao->close();
?>