<?php
require_once('../formulario-cadastro-login/verificacao.php');
$idUsuarioLogado = $_SESSION['id_usuario'];

$servidor = "localhost"; $usuario_db = "root"; $senha_db = ""; $banco = "spark";
$conexao = new mysqli($servidor, $usuario_db, $senha_db, $banco);
if ($conexao->connect_error) { die("Falha na conexão: " . $conexao->connect_error); }

// 1. Busca AMIGOS (status = 'aceita')
$stmt_amigos = $conexao->prepare("
    SELECT u.idUsuario, u.nome, u.username, u.avatar_path
    FROM Usuario u
    JOIN Amizades a ON (a.idSolicitante = u.idUsuario AND a.idRecebedor = ?) OR (a.idRecebedor = u.idUsuario AND a.idSolicitante = ?)
    WHERE a.status = 'aceita'
");
$stmt_amigos->bind_param("ii", $idUsuarioLogado, $idUsuarioLogado);
$stmt_amigos->execute();
$resultado_amigos = $stmt_amigos->get_result();

// 2. Busca SOLICITAÇÕES PENDENTES (que VOCÊ recebeu)
$stmt_solicitacoes = $conexao->prepare("
    SELECT u.idUsuario, u.nome, u.username, u.avatar_path
    FROM Usuario u
    JOIN Amizades a ON a.idUsuarioSolicitante = u.idUsuario
    WHERE a.idUsuarioRecebedor = ? AND a.status = 'pendente'
");
$stmt_solicitacoes->bind_param("i", $idUsuarioLogado);
$stmt_solicitacoes->execute();
$resultado_solicitacoes = $stmt_solicitacoes->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amigos - Spark</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="src/perfil.css"> </head>
<body>
    <header class="appbar">
        <h1>Amigos</h1>
        <i class="fa-solid fa-magnifying-glass" onclick="window.location.href='adicionar_amigo.php'"></i>
    </header>

    <main class="app-content">
        <div class="tabs-container">
            <button class="tab-button active" onclick="showTab('amigos')">Meus Amigos</button>
            <button class="tab-button" onclick="showTab('solicitacoes')">Solicitações</button>
        </div>

        <div id="amigos" class="tab-content active">
            <?php if ($resultado_amigos->num_rows > 0): ?>
                <?php while($amigo = $resultado_amigos->fetch_assoc()):
                    $avatar = !empty($amigo['avatar_path']) ? $amigo['avatar_path'] : 'assets/images/avatar_padrao.png';
                ?>
                <div class="friend-card">
                    <img src="/Spark-main/<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="avatar">
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($amigo['nome']); ?></h3>
                        <p>@<?php echo htmlspecialchars($amigo['username']); ?></p>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty-message">Você ainda não tem amigos. Adicione alguns!</p>
            <?php endif; ?>
            
            <button class="add-friend-btn" onclick="window.location.href='adicionar_amigo.php'">
                <i class="fa-solid fa-plus"></i>
                Adicionar Amigo
            </button>
        </div>

        <div id="solicitacoes" class="tab-content">
            <?php if ($resultado_solicitacoes->num_rows > 0): ?>
                <?php while($solicitacao = $resultado_solicitacoes->fetch_assoc()):
                    $avatar_solicitacao = !empty($solicitacao['avatar_path']) ? $solicitacao['avatar_path'] : 'assets/images/avatar_padrao.png';
                ?>
                <div class="friend-card request">
                    <img src="/Spark-main/<?php echo htmlspecialchars($avatar_solicitacao); ?>" alt="Avatar" class="avatar">
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars($solicitacao['nome']); ?></h3>
                        <p>@<?php echo htmlspecialchars($solicitacao['username']); ?></p>
                    </div>
                    <div class="request-actions">
                        <button class="btn-accept" onclick="responderSolicitacao(<?php echo $solicitacao['idUsuario']; ?>, 'aceitar')"><i class="fa-solid fa-check"></i></button>
                        <button class="btn-decline" onclick="responderSolicitacao(<?php echo $solicitacao['idUsuario']; ?>, 'recusar')"><i class="fa-solid fa-times"></i></button>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="empty-message">Nenhuma solicitação pendente.</p>
            <?php endif; ?>
        </div>
    </main>

    <nav class="bottombar"></nav>

    <script src="src/perfil.js"></script>
</body>
</html>