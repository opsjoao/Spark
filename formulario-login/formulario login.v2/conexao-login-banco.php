<!-- filepath: c:\Users\Aluno\Documents\formulario cadastro eventos\conexao-login-banco.php -->
<?php
// Configurações de conexão com o banco de dados
$host = 'localhost';
$dbname = 'Spark';
$username = 'root'; // Substitua pelo seu usuário do banco de dados
$password = ''; // Substitua pela sua senha do banco de dados

try {
    // Conexão com o banco de dados usando PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $email, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Verifica se o formulário foi enviado
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = $_POST['email']; // Obtém o e-mail do formulário
        $pass = $_POST['password'];

        // Consulta para verificar as credenciais
        $stmt = $pdo->prepare("SELECT nome FROM Usuario WHERE email = :email AND senha = :senha");
        $stmt->bindParam(':email', $user);
        $stmt->bindParam(':senha', $pass);
        $stmt->execute();

        // Verifica se o usuário existe
        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $nomeUsuario = $result['nome'];

            // Inicia a sessão e armazena o nome do usuário
            session_start();
            $_SESSION['nomeUsuario'] = $nomeUsuario;

            // Redireciona para a página de boas-vindas
            header("Location: tela-de-bem-vindo.php");
            exit();
        } else {
            echo "Usuário ou senha inválidos!";
        }
    }
} catch (PDOException $e) {
    echo "Erro na conexão com o banco de dados: " . $e->getMessage();
}
?>