<?php

// 1. Configurações do Banco de Dados
// ATENÇÃO: Substitua com suas credenciais do MySQL!
$servidor = "localhost"; // Geralmente 'localhost'
$usuario = "root";       // Usuário padrão do XAMPP
$senha = "";             // Senha padrão do XAMPP é vazia
$banco = "spark";        // O nome do seu banco de dados

// 2. Criar a Conexão
$conexao = new mysqli($servidor, $usuario, $senha, $banco);

// Checar a conexão
if ($conexao->connect_error) {
    die("Falha na conexão: " . $conexao->connect_error);
}

// 3. Receber os dados do formulário via POST
// Usamos a superglobal $_POST
$idUsuario = $_POST['idUsuario'];
$nomeParque = $_POST['nomeParque'];
$nomeEvento = $_POST['nomeEvento'];
$dataEvento = $_POST['dataEvento'];
$horaInicio = $_POST['horaInicio'];
$horaTermino = $_POST['horaTermino'];
$descricaoEvento = $_POST['descricaoEvento'];

// 4. Lógica: Buscar o ID do Parque a partir do nome
// É crucial usar PREPARED STATEMENTS para evitar SQL Injection!
$stmt_parque = $conexao->prepare("SELECT idParque FROM Parque WHERE nome = ?");
$stmt_parque->bind_param("s", $nomeParque); // "s" significa que é uma string
$stmt_parque->execute();
$resultado_parque = $stmt_parque->get_result();

if ($resultado_parque->num_rows > 0) {
    $parque = $resultado_parque->fetch_assoc();
    $idParque = $parque['idParque'];

    // 5. Inserir o Evento no Banco de Dados
    $stmt_evento = $conexao->prepare(
        "INSERT INTO Evento (idParque, idUsuario, nome, dia, horario_inicio, horario_termino, descricao) VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    // i = integer, s = string
    $stmt_evento->bind_param("iisssss", $idParque, $idUsuario, $nomeEvento, $dataEvento, $horaInicio, $horaTermino, $descricaoEvento);

    if ($stmt_evento->execute()) {
        echo "<h1>Evento cadastrado com sucesso!</h1>";
        echo "<a href='index.html'>Voltar para o formulário</a>";
    } else {
        echo "Erro ao cadastrar o evento: " . $stmt_evento->error;
    }

    $stmt_evento->close();

} else {
    echo "<h1>Erro: Parque não encontrado no banco de dados.</h1>";
    echo "<p>Verifique se o nome '". htmlspecialchars($nomeParque) ."' está correto.</p>";
    echo "<a href='index.html'>Tentar novamente</a>";
}

// 6. Fechar as conexões
$stmt_parque->close();
$conexao->close();

?>