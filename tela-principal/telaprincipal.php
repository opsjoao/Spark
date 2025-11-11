<?php
// --- 1. CONEXÃO COM O BANCO DE DADOS ---
$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "Spark"; // Seu banco de dados se chama "Spark"

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// --- 2. CONSULTA SQL PARA OS EVENTOS ---
$sql_eventos = "
    SELECT
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
        e.dia >= CURDATE()
    ORDER BY
        e.dia ASC, e.horario_inicio ASC;
";

$result_eventos = $conexao->query($sql_eventos);

// --- 3. CONSULTA SQL PARA CATEGORIAS ---
$sql_categorias = "SELECT * FROM Categorias LIMIT 8";
$result_categorias = $conexao->query($sql_categorias);

// --- 4. URL BASE ---
$url_base = '/Spark-main/';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spark - Início</title>
    
    <!-- MUDANÇA AQUI: Carrega o global.css (que você me passou) -->
    <link rel="stylesheet" href="<?php echo $url_base; ?>style.css">
    
    <!-- Carrega o CSS específico da tela (que vai corrigir o global) -->
    <link rel="stylesheet" href="telaprincipal.css">
    
    <!-- Ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

    <!-- 
      MUDANÇA PRINCIPAL AQUI:
      A classe agora é "app-content home" para funcionar com seu global.css
      e permitir correções (com a classe .home)
    -->
    <main class="app-content home">
    
        <!-- 1. BARRA DE BUSCA (Sticky) -->
        <header class="search-header">
            <div class="search-bar">
                <input type="text" placeholder="Buscar...">
                <i class="fas fa-search"></i>
            </div>
        </header>

        <!-- 2. SEÇÃO DE CATEGORIAS -->
        <section class="categories-section">
            <h2>Categorias</h2>
            <div class="category-list">
                <?php
                if ($result_categorias && $result_categorias->num_rows > 0) {
                    while($categoria = $result_categorias->fetch_assoc()) {
                        // Define uma imagem padrão se não houver
                        $img_path = !empty($categoria['imagem_url']) ? $url_base . $categoria['imagem_url'] : $url_base . 'uploads/categorias/default.png';
                ?>
                        <div class="category-item">
                            <img src="<?php echo htmlspecialchars($img_path); ?>" alt="<?php echo htmlspecialchars($categoria['nome']); ?>">
                            <span class="category-label" style="background-color: <?php echo htmlspecialchars($categoria['cor_fundo']); ?>;">
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </span>
                        </div>
                <?php
                    } // Fim do while
                } else {
                    echo "<p style='padding-left: 12px;'>Nenhuma categoria encontrada.</p>";
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

            <?php
                } // Fim do while
            } else {
                echo "<h3>Nenhum evento futuro encontrado no momento.</h3>";
            }
            
            // --- 5. FECHAR A CONEXÃO ---
            if (isset($conexao)) {
                $conexao->close();
            }
            ?>

        </section>

    </main> <!-- FIM DO .app-content -->
    
    <!-- 
      A BOTTOMBAR FICA FORA DO <main>
      MUDANÇA AQUI: Trocado <button> por <a> (links) para a classe .active funcionar
    -->
    <nav class="bottombar">
        <a href="<?php echo $url_base; ?>TelaPerfils/perfil.html" class="nav-btn">
            <i class="fa-solid fa-users"></i>
            <span></span>
        </a>
        <a href="<?php echo $url_base; ?>tela-atividades/atividades.php" class="nav-btn">
            <i class="fa-solid fa-person-walking"></i>
            <span></span>
        </a>
        <a href="<?php echo $url_base; ?>tela-principal/telaprincipal.php" class="nav-btn active">
            <i class="fa-solid fa-house"></i>
            <span></span>
        </a>
        <a href="<?php echo $url_base; ?>telaFavoritos/index.html" class="nav-btn">
            <i class="fa-solid fa-star"></i>
            <span></span>
        </a>
        <a href="<?php echo $url_base; ?>teladeusuario/teladeusuario.php" class="nav-btn">
            <i class="fa-solid fa-user"></i>
            <span></span>
        </a>
    </nav>

</body>
</html>