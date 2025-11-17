<?php
// --- 1. CONEXÃO COM O BANCO DE DADOS ---
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "Spark"; // Seu banco de dados se chama "Spark"
$url_base = '/Spark-main/'; // Caminho base que você pediu

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// --- 1B. LER OS FILTROS DA URL (MUDANÇA PARA MULTI-FILTRO) ---
$filtros_ativos = []; // Agora é um array
if (isset($_GET['categoria']) && is_array($_GET['categoria'])) {
    // Garante que todos os valores são números inteiros (segurança)
    $filtros_ativos = array_map('intval', $_GET['categoria']);
    // Remove zeros ou valores inválidos
    $filtros_ativos = array_filter(array_unique($filtros_ativos));
}

// --- 2. CONSULTA SQL PARA OS EVENTOS ---
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

// --- MUDANÇA (CORREÇÃO DO BUG + MULTI-FILTRO) ---
// Se houver filtros ativos, adiciona a cláusula IN (...)
if (!empty($filtros_ativos)) {
    $lista_de_ids = implode(',', $filtros_ativos); // Cria a string "1,3,5"
    $sql_eventos .= " AND e.idCategoria IN ($lista_de_ids)";
}

// Adiciona a ordenação no final
$sql_eventos .= " ORDER BY e.dia ASC, e.horario_inicio ASC;";

$result_eventos = $conexao->query($sql_eventos);

// --- 3. CONSULTA SQL PARA CATEGORIAS ---
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
    
        <!-- 1. BARRA DE BUSCA (Sticky) -->
        <header class="search-header">
            <div class="search-bar">
                <input type="text" placeholder="Buscar...">
                <a href="<?php echo $url_base; ?>telas-mapas/index.html" class="search-icon-link">
                    <i class="fas fa-search"></i>
                </a>
            </div>
        </header>

        <!-- 2. SEÇÃO DE CATEGORIAS -->
        <section class="categories-section">
            <h2>Categorias</h2>
            <div class="category-list">

                <?php
                // Loop das outras categorias
                if ($result_categorias && $result_categorias->num_rows > 0) {
                    while($categoria = $result_categorias->fetch_assoc()) {
                        
                        $current_cat_id = $categoria['idCategoria'];
                        // --- CORREÇÃO file_exists PARA CATEGORIAS ---
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

                        if (empty($temp_filtros)) {
                            $link_url = "telaprincipal.php"; 
                        } else {
                            $query_string = http_build_query(['categoria' => $temp_filtros]);
                            $link_url = "telaprincipal.php?" . $query_string;
                        }
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
                    } // Fim do while
                }
                ?>
            </div>
        </section>

        <!-- 3. FEED DE EVENTOS -->
        <section class="events-feed">
    
            <?php
            // --- 4. LOOP DOS EVENTOS ---
            if ($result_eventos && $result_eventos->num_rows > 0) {
                while($evento = $result_eventos->fetch_assoc()) {
                    
                    // --- MUDANÇA AQUI: LÓGICA DA IMAGEM DE FUNDO DO EVENTO ---
                    
                    // 1. Define a URL padrão
                    $imagem_padrao_evento = $url_base . "uploads/eventos/default_event.jpg";
                    // 2. Pega o caminho do banco
                    $caminho_relativo_evento = $evento['evento_imagem'];

                    // 3. Verifica se o caminho do banco NÃO está vazio E se o arquivo EXISTE
                    if (!empty($caminho_relativo_evento) && file_exists($_SERVER['DOCUMENT_ROOT'] . $url_base . $caminho_relativo_evento)) {
                        // Se sim, usa a imagem real
                        $imagem_fundo = $url_base . $caminho_relativo_evento;
                    } else {
                        // Se não (vazio OU arquivo não existe), usa a imagem padrão
                        $imagem_fundo = $imagem_padrao_evento;
                    }
                    
                    // --- MUDANÇA AQUI: LÓGICA DO AVATAR DO HOST ---
                    
                    // 1. Define o avatar padrão
                    $avatar_padrao_host = $url_base . "uploads/avatars/default_avatar.png";
                    // 2. Pega o caminho do banco
                    $caminho_relativo_avatar = $evento['host_avatar'];

                    // 3. Verifica se o caminho do banco NÃO está vazio E se o arquivo EXISTE
                    if (!empty($caminho_relativo_avatar) && file_exists($_SERVER['DOCUMENT_ROOT'] . $url_base . $caminho_relativo_avatar)) {
                        // Se sim, usa o avatar real
                        $avatar_host = $url_base . $caminho_relativo_avatar;
                    } else {
                        // Se não (vazio OU arquivo não existe), usa o avatar padrão
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
                } // Fim do while
            } else {
                if (!empty($filtros_ativos)) {
                     echo "<h3 style='padding: 0 12px;'>Nenhum evento encontrado para esta combinação de filtros.</h3>";
                } else {
                     echo "<h3 style='padding: 0 12px;'>Nenhum evento futuro encontrado no momento.</h3>";
                }
            }
            
            if (isset($conexao)) {
                $conexao->close();
            }
            ?>

        </section>

    </main> <!-- FIM DO .app-content -->
    
    <nav class="bottombar">
        <a href="<?php echo $url_base; ?>TelaPerfils/perfil.html" class="nav-btn">
            <i class="fa-solid fa-users"></i>
        </a>
        <a href="<?php echo $url_base; ?>tela-atividades/atividades.php" class="nav-btn">
            <i class="fa-solid fa-person-walking"></i>
        </a>
        <a href="<?php echo $url_base; ?>tela-principal/telaprincipal.php" class="nav-btn active">
            <i class="fa-solid fa-house"></i>
        </a>
        <a href="<?php echo $url_base; ?>telaFavoritos/index.html" class="nav-btn">
            <i class="fa-solid fa-star"></i>
        </a>
        <a href="<?php echo $url_base; ?>teladeusuario/teladeusuario.php" class="nav-btn">
            <i class="fa-solid fa-user"></i>
        </a>
    </nav>

    <!-- 
      MUDANÇA AQUI:
      O script antigo foi substituído por este,
      que usa sessionStorage para salvar e restaurar a posição.
    -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Encontra o carrossel
            const carousel = document.querySelector('.category-list');
            if (!carousel) return; // Sai se o carrossel não existir

            // --- ETAPA 1: AO CARREGAR A PÁGINA ---
            // Verifica se há uma posição de scroll salva na sessão
            const savedScrollPos = sessionStorage.getItem('carouselScrollPos');
            
            if (savedScrollPos) {
                // Se sim, aplica a posição de scroll salva
                carousel.scrollLeft = parseInt(savedScrollPos);
                // Limpa o item da sessão para que ele não afete
                // a próxima navegação (ex: clicar em "Home" na navbar)
                sessionStorage.removeItem('carouselScrollPos');
            }

            // --- ETAPA 2: AO CLICAR EM UM FILTRO ---
            // Pega todos os links de filtro dentro do carrossel
            const filterLinks = carousel.querySelectorAll('.category-item-link');

            // Adiciona um listener de clique a cada link
            filterLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Antes de a página recarregar, salva a posição
                    // ATUAL do scroll na sessão
                    sessionStorage.setItem('carouselScrollPos', carousel.scrollLeft);
                });
            });
        });
    </script>

</body>
</html>
