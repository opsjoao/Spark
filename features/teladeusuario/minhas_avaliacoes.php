<?php
session_start();

// --- CONEXÃO ---
$host = 'localhost'; $db = 'Spark'; $user = 'root'; $pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Erro: " . $conn->connect_error); }

$url_base = '/Spark-main/'; 

// 1. SEGURANÇA
if (!isset($_SESSION['id_usuario'])) {
    header("Location: {$url_base}formulario-login/login.php?erro=restrito");
    exit();
}

$meuId = $_SESSION['id_usuario'];

// 2. BUSCAR AVALIAÇÕES
$sql = "
    (SELECT 
        'evento' as tipo,
        ae.idAvaliacao,
        ae.nota,
        ae.comentario,
        ae.data_avaliacao as data,
        ae.imagem_path,
        e.nome as titulo,
        e.idEvento as id_referencia
     FROM Avaliacao_evento ae
     JOIN Evento e ON ae.idEvento = e.idEvento
     WHERE ae.idUsuario = ?)
    UNION ALL
    (SELECT 
        'parque' as tipo,
        ap.idAvaliacao,
        ap.nota,
        ap.comentario,
        NULL as data,
        NULL as imagem_path,
        p.nome as titulo,
        p.idParque as id_referencia
     FROM Avaliacao_parque ap
     JOIN Parque p ON ap.idParque = p.idParque
     WHERE ap.idUsuario = ?)
    ORDER BY data DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $meuId, $meuId);
$stmt->execute();
$result = $stmt->get_result();

$avaliacoes = [];
while ($row = $result->fetch_assoc()) {
    if ($row['data']) {
        $row['data_formatada'] = date('d/m/Y', strtotime($row['data']));
    } else {
        $row['data_formatada'] = 'Data não registrada';
    }
    
    if (!empty($row['imagem_path'])) {
        $row['imagem_url'] = $url_base . $row['imagem_path'];
    } else {
        $row['imagem_url'] = null;
    }

    $avaliacoes[] = $row;
}

$conn->close();

function renderizarEstrelas($nota) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $nota) {
            $html .= '<i class="fa-solid fa-star star-filled"></i>';
        } else {
            $html .= '<i class="fa-regular fa-star star-empty"></i>';
        }
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title>Minhas Avaliações - Spark</title>
  
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
    :root {
        --cor-primaria: #1A9929;
        --cor-perigo: #D92525;
        --cor-detalhes: #5ED925;
        --texto-claro: #FFFFFF;
        --texto-escuro: #212529;
        --texto-mutado: #6c757d;
        --cor-do-form: #D1D5DB;
        --fundo-claro: #F8F9FA;
        --cor-divisor: #E9ECEF;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { font-family: 'Inter', sans-serif; color: var(--texto-escuro); background-color: var(--fundo-claro); }
    body { display: flex; flex-direction: column !important; min-height: 100vh; width: 100%; }
    
    .appbar { display: flex; align-items: center; justify-content: space-between; padding: 20px; background-color: var(--cor-primaria); color: white; width: 100%; position: sticky; top: 0; z-index: 100; flex-shrink: 0; }
    .appbar h1 { font-size: 1.1rem; font-weight: 600; text-align: center; flex-grow: 1; margin: 0; }
    .icon-btn { background: none; border: none; color: white; font-size: 1.2rem; cursor: pointer; padding: 5px; }

    .app-content { flex: 1; padding: 20px; width: 100%; max-width: 600px; margin: 0 auto; padding-bottom: 90px; }

    .bottombar { background: #ffffff; display: flex; justify-content: space-around; align-items: center; padding: 8px 0; position: fixed; bottom: 0; width: 100%; border-top: 1px solid #ddd; z-index: 1000; }
    .bottombar .nav-btn { background: transparent; border: none; color: #333; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 12px; cursor: pointer; padding: 5px; flex: 1; text-decoration: none !important; outline: none; }
    .bottombar .nav-btn i { font-size: 22px; margin-bottom: 2px; }
    .bottombar .nav-btn.active { color: #fff; font-weight: bold; position: relative; }
    .bottombar .nav-btn.active::before { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 80px; height: 40px; background-color: var(--cor-detalhes); border-radius: 999px; z-index: -1; }
  </style>

  <link rel="stylesheet" href="avaliacoes.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>

  <header class="appbar">
    <button class="icon-btn" onclick="goBack()">
      <i class="fa-solid fa-arrow-left"></i>
    </button>
    <h1>Minhas Avaliações</h1>
    <div style="width: 24px;"></div>
  </header>

  <div class="app-content">
    
    <?php if (count($avaliacoes) > 0): ?>
        <div class="reviews-list">
            <?php foreach ($avaliacoes as $av): ?>
                <!-- ALTERAÇÃO AQUI: Passamos também o ID da Avaliação -->
                <div class="review-card" onclick="irParaPagina('<?php echo $av['tipo']; ?>', <?php echo $av['id_referencia']; ?>, <?php echo $av['idAvaliacao']; ?>)">
                    <div class="review-header">
                        <div class="review-info">
                            <span class="type-badge <?php echo $av['tipo']; ?>">
                                <i class="fa-solid <?php echo $av['tipo'] === 'evento' ? 'fa-calendar-day' : 'fa-tree'; ?>"></i>
                                <?php echo ucfirst($av['tipo']); ?>
                            </span>
                            <h3 class="review-title"><?php echo htmlspecialchars($av['titulo']); ?></h3>
                        </div>
                        <div class="review-date">
                            <?php echo $av['data_formatada']; ?>
                        </div>
                    </div>

                    <div class="review-stars">
                        <?php echo renderizarEstrelas($av['nota']); ?>
                    </div>

                    <p class="review-comment">
                        <?php echo !empty($av['comentario']) ? nl2br(htmlspecialchars($av['comentario'])) : 'Sem comentário.'; ?>
                    </p>

                    <?php if ($av['imagem_url']): ?>
                        <div class="review-image-container">
                            <img src="<?php echo $av['imagem_url']; ?>" alt="Foto da avaliação" class="review-img">
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-regular fa-star"></i>
            <p>Você ainda não avaliou nenhum evento ou parque.</p>
        </div>
    <?php endif; ?>

  </div>

  <script>
    function goBack() {
        window.location.href = 'teladeusuario.php'; 
    }
    function navegar(url) {
        window.location.href = url;
    }
    
    // FUNÇÃO DE REDIRECIONAMENTO COM ID ESPECÍFICO
    function irParaPagina(tipo, idReferencia, idAvaliacao) {
        // Cria a âncora específica para aquela avaliação
        const ancora = `#avaliacao-${idAvaliacao}`; 
        
        if (tipo === 'evento') {
            window.location.href = `../tela-evento/tela-evento.php?id=${idReferencia}${ancora}`;
        } else if (tipo === 'parque') {
            window.location.href = `../tela-parques/parque.php?id=${idReferencia}${ancora}`;
        }
    }
  </script>
</body>
</html>
