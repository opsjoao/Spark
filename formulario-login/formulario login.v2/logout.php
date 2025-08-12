<!-- filepath: c:\Users\Aluno\Documents\formulario cadastro eventos\logout.php -->
<?php
session_start();
session_destroy();
header("Location: formulario-login.html");
exit();
?>