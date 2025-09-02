<?php

// Configurações do banco de dados
$servername = "localhost"; // Geralmente 'localhost' no XAMPP
$username = "root";        // Nome de usuário padrão do XAMPP
$password = "";            // Senha padrão do XAMPP (vazia)
$dbname = "Spark";

// Cria a conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica se a conexão falhou
if ($conn->connect_error) {
    die("A conexão falhou: " . $conn->connect_error);
}

// Verifica se a requisição é do tipo POST e se os dados do formulário foram enviados
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitiza e recupera os dados do formulário
    $nomeInstiuicao = $_POST['nomeInstiuicao'] ?? '';
    $nomeParque = $_POST['nomeParque'] ?? '';
    $nomeEvento = $_POST['nomeEvento'] ?? '';
    $tipoEvento = $_POST['evento'] ?? '';
    $horaInicio = $_POST['horaInicio'] ?? '';
    $horaTermino = $_POST['horaTermino'] ?? '';
    $descricaoEvento = $_POST['descricaoEvento'] ?? '';

    $diaEvento = null;

    // Lógica para determinar a data do evento
    if ($tipoEvento == 'Pontual') {
        $diaEvento = $_POST['dataEvento'] ?? null;
    } else if ($tipoEvento == 'Semanal') {
        $diaSemana = $_POST['dias'] ?? '';
        // Converte o dia da semana para a data da próxima ocorrência
        $diaEvento = date('Y-m-d', strtotime("next " . $diaSemana));
    }

    // --- Busca idParque ---
    $idParque = null;
    $sqlParque = "SELECT idParque FROM Parque WHERE nome = ?";
    $stmtParque = $conn->prepare($sqlParque);
    $stmtParque->bind_param("s", $nomeParque);
    $stmtParque->execute();
    $resultParque = $stmtParque->get_result();

    if ($resultParque->num_rows > 0) {
        $row = $resultParque->fetch_assoc();
        $idParque = $row['idParque'];
    } else {
        echo "Erro: Parque '$nomeParque' não encontrado no banco de dados.";
        $stmtParque->close();
        $conn->close();
        exit();
    }
    $stmtParque->close();

    // --- Busca idInstituicao ---
    $idInstituicao = null;
    $sqlInstituicao = "SELECT idInstituicao FROM Instituicao WHERE nome = ?";
    $stmtInstituicao = $conn->prepare($sqlInstituicao);
    $stmtInstituicao->bind_param("s", $nomeInstiuicao);
    $stmtInstituicao->execute();
    $resultInstituicao = $stmtInstituicao->get_result();

    if ($resultInstituicao->num_rows > 0) {
        $row = $resultInstituicao->fetch_assoc();
        $idInstituicao = $row['idInstituicao'];
    } else {
        echo "Erro: Instituição '$nomeInstiuicao' não encontrada no banco de dados.";
        $stmtInstituicao->close();
        $conn->close();
        exit();
    }
    $stmtInstituicao->close();


    // --- Insere os dados na tabela Evento ---
    // idUsuario é NULL, pois não há um campo de usuário no formulário
    $idUsuario = NULL;

    $sql = "INSERT INTO Evento (idParque, idInstituicao, idUsuario, nome, dia, horario_inicio, horario_termino, descricao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissssss", $idParque, $idInstituicao, $idUsuario, $nomeEvento, $diaEvento, $horaInicio, $horaTermino, $descricaoEvento);

    if ($stmt->execute()) {
        echo "<h1>Evento cadastrado com sucesso!</h1>";
        echo "<p>Parque: " . htmlspecialchars($nomeParque) . "</p>";
        echo "<p>Evento: " . htmlspecialchars($nomeEvento) . "</p>";
        echo "<p>Data: " . htmlspecialchars($diaEvento) . "</p>";
        echo "<p>Início: " . htmlspecialchars($horaInicio) . "</p>";
        echo "<p>Término: " . htmlspecialchars($horaTermino) . "</p>";
    } else {
        echo "Erro ao cadastrar evento: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
