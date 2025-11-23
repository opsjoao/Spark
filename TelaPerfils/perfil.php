<?php
session_start();

// --- 1. VERIFICAÇÃO DE LOGIN ---
if (!isset($_SESSION['id_usuario'])) {
    header("Location: /Spark-main/formulario-login/login.php?erro=restrito");
    exit();
}

$meuId = $_SESSION['id_usuario'];
$host = 'localhost'; $db = 'Spark'; $user = 'root'; $pass = '';
$conn = new mysqli($host, $user, $pass, $db);
$url_base = '/Spark-main/'; 

// --- 2. BUSCAR QUEM EU SIGO ---
$listaSeguindo = [];
$sqlSeguindo = "
    SELECT u.idUsuario, u.nome, u.username, u.avatar_path 
    FROM Usuario u 
    JOIN Seguidores s ON u.idUsuario = s.idSeguido
    WHERE s.idSeguidor = ?
    ORDER BY u.nome ASC
";
$stmt = $conn->prepare($sqlSeguindo);
$stmt->bind_param("i", $meuId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $row['avatar'] = !empty($row['avatar_path']) ? $url_base . $row['avatar_path'] : $url_base . 'assets/images/avatar_padrao.png';
    $row['status_seguir'] = 'seguindo';
    $listaSeguindo[] = $row;
}

// --- 3. BUSCAR MEUS SEGUIDORES ---
$listaSeguidores = [];
$sqlSeguidores = "
    SELECT 
        u.idUsuario, u.nome, u.username, u.avatar_path,
        (SELECT COUNT(*) FROM Seguidores s2 WHERE s2.idSeguidor = ? AND s2.idSeguido = u.idUsuario) as sigo_de_volta
    FROM Usuario u 
    JOIN Seguidores s ON u.idUsuario = s.idSeguidor
    WHERE s.idSeguido = ?
    ORDER BY u.nome ASC
";
$stmt = $conn->prepare($sqlSeguidores);
$stmt->bind_param("ii", $meuId, $meuId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $row['avatar'] = !empty($row['avatar_path']) ? $url_base . $row['avatar_path'] : $url_base . 'assets/images/avatar_padrao.png';
    $row['status_seguir'] = ($row['sigo_de_volta'] > 0) ? 'seguindo' : 'nao_seguindo';
    $listaSeguidores[] = $row;
}

// --- 4. BUSCAR EVENTOS DE AMIGOS (PARA CARD FEED) ---
$listaEventosAmigos = [];
$sqlEventos = "
    SELECT 
        e.*, 
        u.nome as nome_criador, 
        u.username as username_criador,
        u.avatar_path as avatar_criador,
        p.nome as nome_parque
    FROM Evento e
    JOIN Usuario u ON e.idUsuario = u.idUsuario
    JOIN Seguidores s ON u.idUsuario = s.idSeguido
    LEFT JOIN Parque p ON e.idParque = p.idParque
    WHERE s.idSeguidor = ?
    ORDER BY e.dia ASC
";
$stmt = $conn->prepare($sqlEventos);
$stmt->bind_param("i", $meuId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $row['avatar_criador_url'] = !empty($row['avatar_criador']) 
        ? $url_base . $row['avatar_criador'] 
        : $url_base . 'assets/images/avatar_padrao.png';
        
    $row['imagem_evento_url'] = !empty($row['imagem_path']) 
        ? $url_base . $row['imagem_path'] 
        : $url_base . 'assets/images/evento_padrao.jpg';
        
    $listaEventosAmigos[] = $row;
}

$stmt->close();
$conn->close();

$jsonSeguindo = json_encode($listaSeguindo);
$jsonSeguidores = json_encode($listaSeguidores);
$jsonEventos = json_encode($listaEventosAmigos);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title>Spark - Rede</title>
  <link rel="stylesheet" href="src/perfil.css" />
  <link rel="stylesheet" href="<?php echo $url_base; ?>style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" referrerpolicy="no-referrer"/>
</head>
<body>

  <header class="app-header">
    <button class="back-btn" onclick="goBack()"><i class="fa-solid fa-arrow-left"></i></button>
    <h1><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Amigos'; ?></h1>
    <div style="width: 24px;"></div>
  </header>

  <div class="tabs-container">
    <button id="tab-seguindo" class="tab-btn active" onclick="switchTab('seguindo')">
        Seguindo <br><span style="font-size:11px; opacity:0.8;">(<?php echo count($listaSeguindo); ?>)</span>
    </button>
    <button id="tab-seguidores" class="tab-btn" onclick="switchTab('seguidores')">
        Seguidores <br><span style="font-size:11px; opacity:0.8;">(<?php echo count($listaSeguidores); ?>)</span>
    </button>
    <button id="tab-eventos" class="tab-btn" onclick="switchTab('eventos')">
        Eventos <br><span style="font-size:11px; opacity:0.8;">(<?php echo count($listaEventosAmigos); ?>)</span>
    </button>
  </div>

  <section class="search-section" id="search-section">
    <div class="search-container">
      <input type="text" id="searchInput" class="search-input" placeholder="Pesquisar pessoas...">
      <i class="fa-solid fa-magnifying-glass search-icon"></i>
    </div>
  </section>

  <main class="friend-list-container" id="friends-list">
    <!-- Renderizado via JS -->
  </main>

  <nav class="bottombar">
    <button class="nav-btn active" onclick="navegar('/Spark-main/TelaPerfils/perfil.php')"><i class="fa-solid fa-user-group"></i></button>
    <button class="nav-btn" onclick="navegar('/Spark-main/tela-atividades/atividades.php')"><i class="fa-solid fa-person-walking"></i></button>
    <button class="nav-btn" onclick="navegar('/Spark-main/tela-principal/telaprincipal.php')"><i class="fa-solid fa-house"></i></button>
    <button class="nav-btn" onclick="navegar('/Spark-main/telaFavoritos/index.html')"><i class="fa-solid fa-star"></i></button>
    <button class="nav-btn" onclick="navegar('../teladeusuario/teladeusuario.php')"><i class="fa-solid fa-user"></i></button>
  </nav>

  <script>
    const dadosSeguindo = <?php echo $jsonSeguindo; ?>;
    const dadosSeguidores = <?php echo $jsonSeguidores; ?>;
    const dadosEventos = <?php echo $jsonEventos; ?>;
    const meuIdSessao = <?php echo $meuId; ?>;

    let tabAtual = 'seguindo';
    let typingTimer;

    const listaEl = document.getElementById('friends-list');
    const searchInput = document.getElementById('searchInput');
    const searchSection = document.getElementById('search-section');
    
    const tabBtnSeguindo = document.getElementById('tab-seguindo');
    const tabBtnSeguidores = document.getElementById('tab-seguidores');
    const tabBtnEventos = document.getElementById('tab-eventos');

    function switchTab(tab) {
        tabAtual = tab;
        searchInput.value = ''; 
        
        tabBtnSeguindo.classList.remove('active');
        tabBtnSeguidores.classList.remove('active');
        tabBtnEventos.classList.remove('active');

        if (tab === 'seguindo') {
            tabBtnSeguindo.classList.add('active');
            searchSection.style.display = 'block';
            renderizarLista(dadosSeguindo);
        } else if (tab === 'seguidores') {
            tabBtnSeguidores.classList.add('active');
            searchSection.style.display = 'block';
            renderizarLista(dadosSeguidores);
        } else if (tab === 'eventos') {
            tabBtnEventos.classList.add('active');
            searchSection.style.display = 'none';
            renderizarLista(dadosEventos);
        }
    }

    function renderizarLista(lista, msgVazia = null) {
      listaEl.innerHTML = ''; 

      // --- CARD DE EVENTO (FEED) ---
      if (tabAtual === 'eventos') {
        if (lista.length === 0) {
             listaEl.innerHTML = `<p style="text-align:center; color:#888; margin-top:40px;">Nenhum evento de amigos encontrado.</p>`;
             return;
        }
        
        lista.forEach(evt => {
             const dataObj = new Date(evt.dia + 'T00:00:00');
             const diaStr = String(dataObj.getDate()).padStart(2, '0');
             const mesStr = String(dataObj.getMonth() + 1).padStart(2, '0');
             const dataFormatada = `${diaStr}/${mesStr}`;
             const horaFormatada = evt.horario_inicio.substring(0, 5);

             const item = document.createElement('div');
             item.className = 'feed-event-card';
             // REDIRECIONAMENTO DE EVENTO
             item.onclick = () => verEvento(evt.idEvento);
             
             item.innerHTML = `
                 <div class="feed-card-header">
                    <img src="${evt.avatar_criador_url}" class="feed-avatar" alt="Avatar">
                    <div class="feed-user-info">
                        <span class="feed-user-name">${evt.nome_criador}</span>
                        <span class="feed-username">@${evt.username_criador || 'usuario'}</span>
                    </div>
                 </div>
                 <div class="feed-image-container">
                    <img src="${evt.imagem_evento_url}" class="feed-event-img" alt="${evt.nome}">
                 </div>
                 <div class="feed-card-body">
                    <div class="feed-title-row">
                        <h3 class="feed-event-title">${evt.nome}</h3>
                        <span class="feed-event-date">
                            <i class="fa-regular fa-calendar"></i> ${dataFormatada}, ${horaFormatada}
                        </span>
                    </div>
                    <p class="feed-event-location">${evt.nome_parque || 'Local a definir'}</p>
                    <p class="feed-event-desc">${evt.descricao || 'Sem descrição.'}</p>
                 </div>
             `;
             listaEl.appendChild(item);
        });
        return;
      }

      // --- CARD DE PESSOA ---
      const listaFiltrada = lista.filter(u => (u.idUsuario || u.id) != meuIdSessao);

      if(listaFiltrada.length === 0) {
        const msg = msgVazia || (tabAtual === 'seguindo' ? 'Você não segue ninguém.' : 'Você não tem seguidores ainda.');
        listaEl.innerHTML = `<p style="text-align:center; color:#888; margin-top:40px;">${msg}</p>`;
        return;
      }

      listaFiltrada.forEach(u => {
        const idUsuario = u.idUsuario || u.id;
        const textoBtn = u.status_seguir === 'seguindo' ? 'Seguindo' : 'Seguir';
        const classeBtn = u.status_seguir === 'seguindo' ? 'seguindo' : 'nao-seguindo';

        const item = document.createElement('div');
        item.className = 'friend-item';
        item.innerHTML = `
          <div class="friend-content-left" onclick="verPerfil(${idUsuario})">
            <img class="friend-avatar" src="${u.avatar}" alt="${u.nome}">
            <div>
                <span class="friend-name">${u.nome}</span>
                <span class="username-small">@${u.username || 'usuario'}</span>
            </div>
          </div>
          <button id="btn-${idUsuario}" class="btn-follow ${classeBtn}" onclick="toggleSeguir(${idUsuario})">
            ${textoBtn}
          </button>
        `;
        listaEl.appendChild(item);
      });
    }

    searchInput.addEventListener('input', (e) => {
      if (tabAtual === 'eventos') return; 
      clearTimeout(typingTimer);
      const termo = e.target.value.trim();
      if (termo.length === 0) {
        switchTab(tabAtual);
      } else {
        typingTimer = setTimeout(() => {
            fetch(`buscar_usuarios.php?q=${encodeURIComponent(termo)}`)
                .then(r => r.json())
                .then(data => renderizarLista(data, 'Nenhum usuário encontrado.'));
        }, 400);
      }
    });

    function toggleSeguir(id) {
        const btn = document.getElementById(`btn-${id}`);
        if(!btn) return;
        const estaSeguindo = btn.classList.contains('seguindo');
        if (estaSeguindo) {
            btn.className = 'btn-follow nao-seguindo'; btn.innerText = 'Seguir';
        } else {
            btn.className = 'btn-follow seguindo'; btn.innerText = 'Seguindo';
        }
        const formData = new FormData();
        formData.append('id', id);
        fetch('seguir.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => { if(!data.success) alert(data.msg); });
    }

    function goBack() { window.location.href = '/Spark-main/tela-principal/telaprincipal.php'; }
    
    // REDIRECIONAMENTO CORRIGIDO PARA USUÁRIO
    function verPerfil(id) { window.location.href = `../teladeusuario/usuario.php?id=${id}`; }
    
    function navegar(url) { window.location.href = url; }
    
    // REDIRECIONAMENTO CORRIGIDO PARA EVENTO
    function verEvento(id) { window.location.href = `../tela-evento/tela-evento.php?id=${id}`; }

    switchTab('seguindo');
  </script>
</body>
</html>