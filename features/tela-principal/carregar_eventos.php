<?php
// carregar_eventos.php
// Este arquivo retorna eventos em formato JSON para o Infinite Scroll

// Desativa erros na tela para não quebrar o JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "Spark";
$url_base = '/Spark-main/'; // Ajuste se necessário

$conexao = new mysqli($servidor, $usuario, $senha, $banco);

if ($conexao->connect_error) {
    echo json_encode(['erro' => 'Falha na conexão']);
    exit;
}

$conexao->set_charset("utf8mb4");

// --- 1. RECEBE OS PARÂMETROS ---
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 5; // Quantos eventos carregar por vez
$offset = ($pagina - 1) * $limite;

// Filtros (Cópia da lógica do telaprincipal.php)
$filtros_ativos = [];
if (isset($_GET['categoria']) && is_array($_GET['categoria'])) {
    $filtros_ativos = array_map('intval', $_GET['categoria']);
    $filtros_ativos = array_filter(array_unique($filtros_ativos));
}

$termo_busca = "";
if (isset($_GET['busca'])) {
    $termo_busca = trim($conexao->real_escape_string($_GET['busca']));
}

// --- 2. CONSTRÓI A CONSULTA ---
$sql = "
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

// Aplica Filtros de Categoria
if (!empty($filtros_ativos)) {
    $lista_de_ids = implode(',', $filtros_ativos);
    $sql .= " AND e.idCategoria IN ($lista_de_ids)";
}

// Aplica Busca (Texto)
if (!empty($termo_busca)) {
    $sql .= " AND (LOWER(e.nome) LIKE LOWER('%$termo_busca%') OR LOWER(p.nome) LIKE LOWER('%$termo_busca%'))";
}

// Ordenação e Paginação (O SEGREDO ESTÁ AQUI)
$sql .= " ORDER BY e.dia ASC, e.horario_inicio ASC LIMIT $limite OFFSET $offset";

$result = $conexao->query($sql);
$eventos = [];

if ($result && $result->num_rows > 0) {
    while($evento = $result->fetch_assoc()) {
        
        // --- RESOLVE AS IMAGENS (Lógica centralizada aqui) ---
        
        // Imagem do Evento
        $imagem_padrao_evento = $url_base . "uploads/eventos/default_event.jpg";
        $caminho_relativo_evento = $evento['evento_imagem'];
        $imagem_fundo = $imagem_padrao_evento;
        
        if (!empty($caminho_relativo_evento) && file_exists($_SERVER['DOCUMENT_ROOT'] . $url_base . $caminho_relativo_evento)) {
            $imagem_fundo = $url_base . $caminho_relativo_evento;
        }

        // Avatar do Host
        $avatar_padrao_host = $url_base . "uploads/avatars/default_avatar.jpg";
        $caminho_relativo_avatar = $evento['host_avatar'];
        $avatar_host = $avatar_padrao_host;

        if (!empty($caminho_relativo_avatar) && file_exists($_SERVER['DOCUMENT_ROOT'] . $url_base . $caminho_relativo_avatar)) {
            $avatar_host = $url_base . $caminho_relativo_avatar;
        }

        // Adiciona ao array de resposta
        $eventos[] = [
            'id' => $evento['idEvento'],
            'nome' => $evento['evento_nome'],
            'parque' => $evento['parque_nome'],
            'host_nome' => $evento['host_nome'],
            'imagem' => $imagem_fundo,
            'avatar' => $avatar_host
        ];
    }
}

echo json_encode($eventos);
$conexao->close();
?>
