<!-- filepath: c:\Users\Aluno\Documents\formulario cadastro eventos\teste-conexao.php -->
<?php
// Configurações de conexão com o banco de dados
$host = 'localhost';
$dbname = 'Spark';
$username = 'root'; // Substitua pelo seu usuário do banco de dados
$password = ''; // Substitua pela sua senha do banco de dados

try {
    // Tenta estabelecer a conexão
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Conexão com o banco de dados estabelecida com sucesso!";
} catch (PDOException $e) {
    // Exibe mensagem de erro caso a conexão falhe
    echo "Erro na conexão com o banco de dados: " . $e->getMessage();
}
?>