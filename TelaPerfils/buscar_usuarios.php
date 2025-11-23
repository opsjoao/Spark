<?php
header('Content-Type: application/json');
session_start();

// --- CONEXÃO ---
$host = 'localhost'; $db = 'Spark'; $user = 'root'; $pass = '';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { echo json_encode([]); exit; }

// VERIFICAÇÃO DE LOGIN CORRIGIDA
if (!isset($_SESSION['id_usuario'])) { echo json_encode([]); exit; }
$meuId = (int)$_SESSION['id_usuario']; // <--- CORRIGIDO

$url_base = '/Spark-main/';
$termo = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($termo) < 1) { echo json_encode([]); exit; }

// SQL DE BUSCA
$sql = "
    SELECT 
        u.idUsuario, 
        u.nome, 
        u.username, 
        u.avatar_path,
        (SELECT COUNT(*) FROM Seguidores s WHERE s.idSeguidor = ? AND s.idSeguido = u.idUsuario) as seguindo
    FROM Usuario u
    WHERE (u.nome LIKE ? OR u.username LIKE ?)
      AND u.idUsuario <> ? 
    LIMIT 20
";

$stmt = $conn->prepare($sql);
$termoLike = "%" . $termo . "%";
$stmt->bind_param("issi", $meuId, $termoLike, $termoLike, $meuId);

$stmt->execute();
$result = $stmt->get_result();

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $row['avatar'] = !empty($row['avatar_path']) ? $url_base . $row['avatar_path'] : $url_base . 'assets/images/avatar_padrao.png';
    $row['id'] = $row['idUsuario'];
    $row['status_seguir'] = ($row['seguindo'] > 0) ? 'seguindo' : 'nao_seguindo';
    $usuarios[] = $row;
}

echo json_encode($usuarios);
$stmt->close();
$conn->close();
?>