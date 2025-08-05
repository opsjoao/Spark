<?php

    $email = $_POST['txtEmail'];
    $senha = $_POST['txtSenha'];
    echo $email;
    echo "<br/>";
    echo $senha;

    $sql = "select * from tb_usuario where email = ? AND senha = ?";
    $conn = new mysqli("localhost","root","","test");
    if($conn->connect_error){
        echo("<br/> Erro ao abrir conexao <br/>");  
    }else{
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $senha);
        $stmt->execute();
        // Obtém os resultados
        $result = $stmt->get_result();
        $achouUsuario = false;
        $nome = "";
        while ($row = $result->fetch_assoc()) {
                $nome =  $row['nome'];
                echo "ID: " . $row['id'] . "<br>";
                echo "Nome: " . $row['nome'] . "<br>";
                echo "Email: " . $row['email'] . "<br>";
                echo "<hr>";
                $achouUsuario = true;
        }
            
          


    }
    $conn->close();
    if($achouUsuario==false){
        header('Location:index.html');
    }else{
        header('Location:dashboard.php?nome='.$nome);
    }

?>