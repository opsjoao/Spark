<?php
// --- 1. CONEXÃO ---
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "Spark"; 
$url_base = '/Spark-main/'; 

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// --- 1B. FILTROS ---
$filtros_ativos = [];
if (isset($_GET['categoria']) && is_array($_GET['categoria'])) {
    $filtros_ativos = array_map('intval', $_GET['categoria']);
    $filtros_ativos = array_filter(array_unique($filtros_ativos));
}

$termo_busca = "";
if (isset($_GET['busca'])) {
    $termo_busca = trim($_GET['busca']);
}

$tem_busca_ativa = !empty($termo_busca) || !empty($filtros_ativos);

// --- 2. CONSULTA SQL INICIAL (CARREGA SÓ OS 5 PRIMEIROS) ---
$sql_eventos = "
    SELECT
        e.idEvento, 
        e.nome AS evento_nome,
        e.imagem_path AS evento_imagem,
        p.nome AS parque_nome,
        COALESCE(u_criador.nome, i.nome) AS host_nome,
        COALESCE(u_criador.avatar_path, u_inst.avatar_path) AS host_avatar
    FROM
        Evento AS e
    JOIN
        Parque AS p ON e.idParque = p.idParque
    LEFT JOIN
        Usuario AS u_criador ON e.idUsuario = u_criador.idUsuario
    LEFT JOIN
        Instituicao AS i ON e.idInstituicao = i.idInstituicao
    LEFT JOIN
        Usuario AS u_inst ON i.idUsuario = u_inst.idUsuario
    WHERE
        TIMESTAMP(e.dia, e.horario_termino) >= NOW()
";

if (!empty($filtros_ativos)) {
    $lista_de_ids = implode(',', $filtros_ativos);
    $sql_eventos .= " AND e.idCategoria IN ($lista_de_ids)";
}

if (!empty($termo_busca)) {
    $busca_segura = $conexao->real_escape_string($termo_busca);
    $sql_eventos .= " AND (LOWER(e.nome) LIKE LOWER('%$busca_segura%') OR LOWER(p.nome) LIKE LOWER('%$busca_segura%'))";
}

// MUDANÇA: Adicionado LIMIT 5 para o carregamento inicial ser rápido
$sql_eventos .= " ORDER BY e.dia ASC, e.horario_inicio ASC LIMIT 5;";

$result_eventos = $conexao->query($sql_eventos);

// --- 3. CATEGORIAS ---
$sql_categorias = "SELECT * FROM Categorias LIMIT 8";
$result_categorias = $conexao->query($sql_categorias);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spark - Início</title>
    
    <link rel="stylesheet" href="<?php echo $url_base; ?>style.css">
    <link rel="stylesheet" href="telaprincipal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

    <main class="app-content home">
    
        <header class="search-header">
            <div class="search-bar">
                <?php if ($tem_busca_ativa): ?>
                    <a href="telaprincipal.php" class="back-icon-link" title="Limpar busca">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                <?php endif; ?>

                <form action="telaprincipal.php" method="GET" class="search-form-container" style="position: relative;">
                    <?php foreach($filtros_ativos as $cat_id): ?>
                        <input type="hidden" name="categoria[]" value="<?php echo $cat_id; ?>">
                    <?php endforeach; ?>
                    <input type="text" id="input-busca" name="busca" placeholder="Buscar evento ou parque..." value="<?php echo htmlspecialchars($termo_busca); ?>" autocomplete="off">
                    <div id="sugestoes-lista" class="suggestions-dropdown"></div>
                </form>
                
                <a href="<?php echo $url_base; ?>telas-mapas/index.html" class="search-icon-link">
                    <i class="fas fa-search"></i>
                </a>
            </div>
        </header>

        <section class="categories-section">
            <h2>Categorias</h2>
            <div class="category-list">
                <?php
                if ($result_categorias && $result_categorias->num_rows > 0) {
                    while($categoria = $result_categorias->fetch_assoc()) {
                        $current_cat_id = $categoria['idCategoria'];
                        $img_path_default = $url_base . 'uploads/categorias/default.png';
                        $img_path_relativo = $categoria['imagem_url'];
                        if (!empty($img_path_relativo) && file_exists($_SERVER['DOCUMENT_ROOT'] . $url_base . $img_path_relativo)) {
                            $img_path = $url_base . $img_path_relativo;
                        } else {
                            $img_path = $img_path_default;
                        }
                        
                        $is_active = in_array($current_cat_id, $filtros_ativos);
                        $classe_ativa = $is_active ? 'active' : '';
                        $temp_filtros = $filtros_ativos; 
                        if ($is_active) {
                            unset($temp_filtros[array_search($current_cat_id, $temp_filtros)]);
                        } else {
                            $temp_filtros[] = $current_cat_id;
                        }
                        $params = ['categoria' => $temp_filtros];
                        if (!empty($termo_busca)) { $params['busca'] = $termo_busca; }
                        if (empty($temp_filtros) && empty($termo_busca)) { $link_url = "telaprincipal.php"; } 
                        else { $query_string = http_build_query($params); $link_url = "telaprincipal.php?" . $query_string; }
                ?>
                        <a href="<?php echo $link_url; ?>" class="category-item-link <?php echo $classe_ativa; ?>">
                            <div class="category-item">
                                <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($categoria['nome']); ?>">
                                <span class="category-label" style="background-color: <?php echo htmlspecialchars($categoria['cor_fundo']); ?>;">
                                    <?php echo htmlspecialchars($categoria['nome']); ?>
                                </span>
                            </div>
                        </a>
                <?php
                    } 
                }
                ?>
            </div>
        </section>

        <!-- FEED DE EVENTOS -->
        <section class="events-feed" id="feed-container">
            <?php
            if ($result_eventos && $result_eventos->num_rows > 0) {
                while($evento = $result_eventos->fetch_assoc()) {
                    // Lógica PHP de renderização inicial (IGUAL AO ANTES)
                    $imagem_padrao_evento = $url_base . "uploads/eventos/default_event.jpg";
                    $caminho_relativo_evento = $evento['evento_imagem'];
                    if (!empty($caminho_relativo_evento) && file_exists($_SERVER['DOCUMENT_ROOT'] . $url_base . $caminho_relativo_evento)) {
                        $imagem_fundo = $url_base . $caminho_relativo_evento;
                    } else {
                        $imagem_fundo = $imagem_padrao_evento;
                    }
                    $avatar_padrao_host = $url_base . "uploads/avatars/default_avatar.jpg";
                    $caminho_relativo_avatar = $evento['host_avatar'];
                    if (!empty($caminho_relativo_avatar) && file_exists($_SERVER['DOCUMENT_ROOT'] . $url_base . $caminho_relativo_avatar)) {
                        $avatar_host = $url_base . $caminho_relativo_avatar;
                    } else {
                        $avatar_host = $avatar_padrao_host;
                    }
            ?>
            <!-- Card Renderizado pelo PHP -->
            <a href="<?php echo $url_base; ?>tela-evento/tela-evento.php?id=<?php echo $evento['idEvento']; ?>" class="event-card-link">
                <div class="event-card featured" style="background-image: url('<?php echo htmlspecialchars($imagem_fundo); ?>');">
                    <div class="card-content">
                        <div class="event-info">
                            <h3><?php echo htmlspecialchars($evento['evento_nome']); ?></h3>
                            <p><?php echo htmlspecialchars($evento['parque_nome']); ?></p>
                        </div>
                        <div class="event-host">
                            <img src="<?php echo htmlspecialchars($avatar_host); ?>" alt="Avatar do Host">
                            <span><?php echo htmlspecialchars($evento['host_nome']); ?></span>
                        </div>
                    </div>
                </div>
            </a>
            <?php
                } 
            } else {
                // ... (Lógica de "Nenhum evento encontrado" ou sugestões) ...
                if (!empty($termo_busca)) {
                     // ... (Lógica de sugestões mantida igual) ...
                     $busca_segura = $conexao->real_escape_string($termo_busca);
                     $sql_sugestoes = "SELECT e.idEvento, e.nome AS evento_nome, e.imagem_path AS evento_imagem, p.nome AS parque_nome, COALESCE(u_criador.nome, i.nome) AS host_nome, COALESCE(u_criador.avatar_path, u_inst.avatar_path) AS host_avatar FROM Evento AS e JOIN Parque AS p ON e.idParque = p.idParque LEFT JOIN Usuario AS u_criador ON e.idUsuario = u_criador.idUsuario LEFT JOIN Instituicao AS i ON e.idInstituicao = i.idInstituicao LEFT JOIN Usuario AS u_inst ON i.idUsuario = u_inst.idUsuario WHERE TIMESTAMP(e.dia, e.horario_termino) >= NOW() AND (LOWER(e.nome) LIKE LOWER('%$busca_segura%') OR LOWER(p.nome) LIKE LOWER('%$busca_segura%')) ORDER BY e.dia ASC LIMIT 3";
                     $result_sugestoes = $conexao->query($sql_sugestoes);
                     echo "<div style='padding: 0 12px; margin-bottom: 20px;'>";
                     if ($result_sugestoes && $result_sugestoes->num_rows > 0 && !empty($filtros_ativos)) {
                         echo "<h3>Não encontramos \"".htmlspecialchars($termo_busca)."\" nas categorias selecionadas.</h3>";
                         echo "<p style='color: #666; margin-top: 5px;'>Mas encontramos estes eventos em outras categorias:</p>";
                     } else {
                         echo "<h3>Nenhum evento encontrado para \"".htmlspecialchars($termo_busca)."\".</h3>";
                     }
                     echo "</div>";
                     // Loop de sugestões (simplificado aqui, mas está no código)
                     if ($result_sugestoes && $result_sugestoes->num_rows > 0) {
                        while($evento = $result_sugestoes->fetch_assoc()) {
                            // ... (Renderiza card de sugestão - igual ao PHP) ...
                             $imagem_padrao_evento = $url_base . "uploads/eventos/default_event.jpg";
                            $caminho_relativo_evento = $evento['evento_imagem'];
                            if (!empty($caminho_relativo_evento) && file_exists($_SERVER['DOCUMENT_ROOT'] . $url_base . $caminho_relativo_evento)) {
                                $imagem_fundo = $url_base . $caminho_relativo_evento;
                            } else {
                                $imagem_fundo = $imagem_padrao_evento;
                            }
                            $avatar_padrao_host = $url_base . "uploads/avatars/default_avatar.png";
                            $caminho_relativo_avatar = $evento['host_avatar'];
                            if (!empty($caminho_relativo_avatar) && file_exists($_SERVER['DOCUMENT_ROOT'] . $url_base . $caminho_relativo_avatar)) {
                                $avatar_host = $url_base . $caminho_relativo_avatar;
                            } else {
                                $avatar_host = $avatar_padrao_host;
                            }
                            ?>
                            <a href="<?php echo $url_base; ?>tela-evento/tela-evento.php?id=<?php echo $evento['idEvento']; ?>" class="event-card-link">
                                <div class="event-card featured" style="background-image: url('<?php echo htmlspecialchars($imagem_fundo); ?>');">
                                    <div class="card-content">
                                        <div class="event-info">
                                            <h3><?php echo htmlspecialchars($evento['evento_nome']); ?></h3>
                                            <p><?php echo htmlspecialchars($evento['parque_nome']); ?></p>
                                        </div>
                                        <div class="event-host">
                                            <img src="<?php echo htmlspecialchars($avatar_host); ?>" alt="Avatar do Host">
                                            <span><?php echo htmlspecialchars($evento['host_nome']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            <?php
                        }
                     }
                } elseif (!empty($filtros_ativos)) {
                     echo "<h3 style='padding: 0 12px;'>Nenhum evento encontrado para esta combinação de filtros.</h3>";
                } else {
                     echo "<h3 style='padding: 0 12px;'>Nenhum evento futuro encontrado no momento.</h3>";
                }
            }
            if (isset($conexao)) { $conexao->close(); }
            ?>
            
            <!-- MUDANÇA: Elemento Sentinela (Gatilho do Scroll) -->
            <!-- Quando este elemento aparecer na tela, o JS carrega mais -->
            <div id="sentinela" style="height: 50px; width: 100%; display: flex; justify-content: center; align-items: center;">
                <!-- Ícone de loading (opcional) -->
                <i class="fas fa-spinner fa-spin" style="color: #ccc; display: none;" id="loading-icon"></i>
            </div>

        </section>

    </main> 
    
    <nav class="bottombar">
        <!-- ... (Navbar igual) ... -->
        <a href="<?php echo $url_base; ?>TelaPerfils/perfil.php" class="nav-btn">
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

    <!-- SCRIPTS JS -->
    <script>
        // 1. Script do Scroll do Carrossel
        document.addEventListener("DOMContentLoaded", function() {
            const carousel = document.querySelector('.category-list');
            if (!carousel) return; 
            const savedScrollPos = sessionStorage.getItem('carouselScrollPos');
            if (savedScrollPos) { carousel.scrollLeft = parseInt(savedScrollPos); sessionStorage.removeItem('carouselScrollPos'); }
            const filterLinks = carousel.querySelectorAll('.category-item-link');
            filterLinks.forEach(link => { link.addEventListener('click', function() { sessionStorage.setItem('carouselScrollPos', carousel.scrollLeft); }); });
        });

        // 2. Script da Busca Rápida (Autocomplete)
        const inputBusca = document.getElementById('input-busca');
        const listaSugestoes = document.getElementById('sugestoes-lista');
        const urlBase = "<?php echo $url_base; ?>"; 
        const pathAtual = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
        const urlBusca = pathAtual + '/busca_rapida.php';

        if(inputBusca) {
            inputBusca.addEventListener('input', function() {
                const termo = this.value;
                if (termo.length < 2) {
                    listaSugestoes.innerHTML = '';
                    listaSugestoes.style.display = 'none';
                    return;
                }
                fetch(urlBusca + '?termo=' + encodeURIComponent(termo))
                    .then(response => {
                        if (!response.ok) { throw new Error("Erro na rede"); }
                        return response.json();
                    })
                    .then(data => {
                        listaSugestoes.innerHTML = '';
                        if (data && data.length > 0) {
                            listaSugestoes.style.display = 'block';
                            data.forEach(evento => {
                                const div = document.createElement('div');
                                div.className = 'suggestion-item';
                                div.innerHTML = `<i class="fas fa-calendar-alt"></i> ${evento.nome}`;
                                div.onclick = function() {
                                    window.location.href = urlBase + 'tela-evento/tela-evento.php?id=' + evento.idEvento;
                                };
                                listaSugestoes.appendChild(div);
                            });
                        } else {
                            listaSugestoes.style.display = 'none';
                        }
                    })
                    .catch(err => {
                        console.error('Erro na busca:', err);
                        listaSugestoes.style.display = 'none';
                    });
            });

            document.addEventListener('click', function(e) {
                if (e.target !== inputBusca && e.target !== listaSugestoes) {
                    listaSugestoes.style.display = 'none';
                }
            });
        }

        // 3. NOVO: Script do Infinite Scroll
        let paginaAtual = 1; // Já carregamos a pág 1 com PHP
        let carregando = false;
        const sentinela = document.getElementById('sentinela');
        const loadingIcon = document.getElementById('loading-icon');
        const feedContainer = document.getElementById('feed-container');
        
        // Pega os parâmetros da URL atual (categorias, busca)
        const params = new URLSearchParams(window.location.search);

        const carregarMaisEventos = () => {
            if (carregando) return;
            carregando = true;
            loadingIcon.style.display = 'block';

            // Prepara a URL da API
            params.set('pagina', paginaAtual + 1);
            const urlAPI = pathAtual + '/carregar_eventos.php?' + params.toString();

            fetch(urlAPI)
                .then(response => response.json())
                .then(eventos => {
                    if (eventos && eventos.length > 0) {
                        paginaAtual++;
                        
                        eventos.forEach(evento => {
                            // Cria o HTML do card via JS
                            const link = document.createElement('a');
                            link.href = urlBase + 'tela-evento/tela-evento.php?id=' + evento.id;
                            link.className = 'event-card-link';
                            
                            link.innerHTML = `
                                <div class="event-card featured" style="background-image: url('${evento.imagem}');">
                                    <div class="card-content">
                                        <div class="event-info">
                                            <h3>${evento.nome}</h3>
                                            <p>${evento.parque}</p>
                                        </div>
                                        <div class="event-host">
                                            <img src="${evento.avatar}" alt="Avatar do Host">
                                            <span>${evento.host_nome}</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                            // Insere ANTES do sentinela
                            feedContainer.insertBefore(link, sentinela);
                        });
                    } else {
                        // Acabaram os eventos, remove o sentinela
                        if(sentinela) sentinela.remove();
                    }
                })
                .catch(err => console.error('Erro ao carregar mais:', err))
                .finally(() => {
                    carregando = false;
                    loadingIcon.style.display = 'none';
                });
        };

        // Observador de Interseção
        if (sentinela) {
            const observer = new IntersectionObserver((entries) => {
                if (entries[0].isIntersecting) {
                    carregarMaisEventos();
                }
            }, { rootMargin: '100px' }); // Carrega 100px antes de chegar no fim

            observer.observe(sentinela);
        }

    </script>

</body>
</html>
