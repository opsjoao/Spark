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
"; // --- MUDANÇA: Ponto e vírgula removido para adicionar o filtro

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
    
    <!-- 
      MUDANÇA: Corrigido o caminho do seu CSS global
      para 'style.css' (baseado no seu pedido).
    -->
    <link rel="stylesheet" href="<?php echo $url_base; ?>style.css">
    
    <!-- Carrega o CSS específico da tela -->
    <link rel="stylesheet" href="telaprincipal.css">
    
    <!-- Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

    <!-- 
      Seu HTML aqui está correto, usando as classes
      "app-content home" do seu global.css 
    -->
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
                        $img_path = !empty($categoria['imagem_url']) ? $url_base . $categoria['imagem_url'] : $url_base . 'uploads/categorias/default.png';
                        
                        // --- LÓGICA DO FILTRO ATIVO (MUDANÇA) ---
                        // Verifica se o ID atual está no array de filtros ativos
                        $is_active = in_array($current_cat_id, $filtros_ativos);
                        $classe_ativa = $is_active ? 'active' : '';

                        // --- LÓGICA DE MONTAGEM DA URL (MUDANÇA) ---
                        $temp_filtros = $filtros_ativos; // Cria uma cópia

                        if ($is_active) {
                            // Se JÁ ESTÁ ATIVO, remove este ID da lista
                            // (array_search encontra a "chave" do ID no array)
                            unset($temp_filtros[array_search($current_cat_id, $temp_filtros)]);
                        } else {
                            // Se NÃO ESTÁ ATIVO, adiciona este ID à lista
                            $temp_filtros[] = $current_cat_id;
                        }

                        // Monta a URL
                        if (empty($temp_filtros)) {
                            $link_url = "telaprincipal.php"; // Sem filtros, limpa a URL
                        } else {
                            // Monta a query string: ?categoria[]=1&categoria[]=3
                            $query_string = http_build_query(['categoria' => $temp_filtros]);
                            $link_url = "telaprincipal.php?" . $query_string;
                        }
                ?>
                        <!-- O href agora usa a $link_url dinâmica -->
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
                    
                    // --- LÓGICA DA IMAGEM DE FUNDO DO EVENTO ---
                    $imagem_padrao_evento = $url_base . "uploads/eventos/default_event.jpg";
                    $caminho_relativo_evento = $evento['evento_imagem'];

                    if (!empty($caminho_relativo_evento)) {
                        $imagem_fundo = $url_base . $caminho_relativo_evento;
                    } else {
                        $imagem_fundo = $imagem_padrao_evento;
                    }
                    
                    // --- LÓGICA DO AVATAR DO HOST ---
                    $avatar_padrao_host = $url_base . "uploads/avatars/default_avatar.png";
                    $caminho_relativo_avatar = $evento['host_avatar'];

                    if (!empty($caminho_relativo_avatar)) {
                        $avatar_host = $url_base . $caminho_relativo_avatar;
                    } else {
                        $avatar_host = $avatar_padrao_host;
                    }
            ?>
            
            <!-- Link para o evento (usei o caminho que você forneceu) -->
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
                // MUDANÇA: Mensagem personalizada
                if (!empty($filtros_ativos)) {
                     echo "<h3 style='padding: 0 12px;'>Nenhum evento encontrado para esta combinação de filtros.</h3>";
                } else {
                     echo "<h3 style='padding: 0 12px;'>Nenhum evento futuro encontrado no momento.</h3>";
                }
            }
            
            // --- 5. FECHAR A CONEXÃO ---
            if (isset($conexao)) {
                $conexao->close();
            }
            ?>

        </section>

    </main> <!-- FIM DO .app-content -->
    
    <!-- 
      MUDANÇA: Removidos os <span> de dentro dos links
      da navbar, conforme seu pedido.
    -->
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

</body>
</html>
