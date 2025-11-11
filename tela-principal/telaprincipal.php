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

// --- 2. CONSULTA SQL PARA OS EVENTOS (A MÁGICA ACONTECE AQUI) ---
/*
 * Esta consulta faz o seguinte:
 * 1. Pega dados do 'Evento' (e).
 * 2. Pega o nome do 'Parque' (p) usando o idParque.
 * 3. Pega o nome do 'Usuario' (u_criador) SE o evento foi criado por um usuário.
 * 4. Pega o nome da 'Instituicao' (i) SE foi criado por uma instituição.
 * 5. Pega o 'avatar_path' do usuário criador (u_criador) OU do usuário ligado à instituição (u_inst).
 * 6. Usa COALESCE para mostrar o nome do 'host' (seja usuário ou instituição).
 * 7. Usa COALESCE para mostrar o avatar (seja do usuário ou da instituição).
 * 8. Filtra (WHERE) para mostrar apenas eventos de HOJE (CURDATE()) em diante.
 * 9. Ordena (ORDER BY) para mostrar os eventos mais próximos primeiro.
 */
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

 $sql_categorias = "SELECT * FROM Categorias LIMIT 8";
 $result_categorias = $conexao->query($sql_categorias);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spark - Início</title>
    <link rel="stylesheet" href="telaprincipal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body>

    <main class="container">
        <header class="search-header">
            <div class="search-bar">
                <input type="text" placeholder="Buscar...">
                <i class="fas fa-search"></i>
            </div>
        </header>

        <section class="categories-section">
            <h2>Categorias</h2>
            <div class="category-list">
                <?php
                
                if ($result_categorias && $result_categorias->num_rows > 0) {
                    while($categoria = $result_categorias->fetch_assoc()) {
                ?>
                        <div class="category-item">
                            <img src="<?php echo htmlspecialchars($categoria['imagem_url']); ?>" alt="<?php echo htmlspecialchars($categoria['nome']); ?>">
                            <span class="category-label" style="background-color: <?php echo htmlspecialchars($categoria['cor_fundo']); ?>;">
                                <?php echo htmlspecialchars($categoria['nome']); ?>
                            </span>
                        </div>
                <?php
                    } // Fim do while
                } else {
                    echo "<p>Crie a tabela 'Categorias' para vê-las aqui.</p>";
                }
                
                ?>
            </div>
        </section>

        <section class="events-feed">
    
    <?php
    // --- 4. LOOP DOS EVENTOS ---
    // Verificamos se a consulta foi bem-sucedida E se retornou mais de 0 linhas
    if ($result_eventos && $result_eventos->num_rows > 0) {
        
        // Defina a URL base DO SEU PROJETO. 
        // Se o seu site está em http://localhost/Spark-main/, isso está correto.
        $base_url = "/Spark-main/"; 

        while($evento = $result_eventos->fetch_assoc()) {
            
            // --- LÓGICA DA IMAGEM DE FUNDO DO EVENTO ---
            $imagem_padrao_evento = $base_url . "uploads/eventos/default_event.jpg"; // Crie uma imagem padrão nesta pasta
            $caminho_relativo_evento = $evento['evento_imagem']; // ex: 'uploads/evento_piquenique.jpg'

            if (!empty($caminho_relativo_evento)) {
                $imagem_fundo = $base_url . $caminho_relativo_evento;
            } else {
                $imagem_fundo = $imagem_padrao_evento;
            }
            
            // --- LÓGICA DO AVATAR DO HOST ---
            $avatar_padrao_host = $base_url . "uploads/avatars/default_avatar.png"; // Crie um avatar padrão nesta pasta
            $caminho_relativo_avatar = $evento['host_avatar']; // ex: 'uploads/avatars/murilo.jpg'

            if (!empty($caminho_relativo_avatar)) {
                $avatar_host = $base_url . $caminho_relativo_avatar;
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
        // Se o IF falhar (0 linhas), esta mensagem DEVE aparecer.
        echo "<h3>Nenhum evento futuro encontrado no momento.</h3>";
    }
    
    // --- 5. FECHAR A CONEXÃO ---
    if (isset($conexao)) { // Boa prática: verificar se a conexão existe antes de fechar
        $conexao->close();
    }
    ?>

</section>

    </main> 
    <nav class="bottom-navbar">
        <a href="telaprincipal.php" class="nav-item active">
            <i class="fas fa-home"></i>
            <span>Início</span>
        </a>
        <a href="mapa.php" class="nav-item">
            <i class="fas fa-map-marked-alt"></i>
            <span>Mapa</span>
        </a>
        <a href="criar_evento.php" class="nav-item">
            <i class="fas fa-plus-circle fa-lg"></i>
        </a>
        <a href="amigos.php" class="nav-item">
            <i class="fas fa-users"></i>
            <span>Amigos</span>
        </a>
        <a href="perfil.php" class="nav-item">
            <i class="fas fa-user"></i>
            <span>Perfil</span>
        </a>
    </nav>
</body>
</html>