<?php
// Você pode colocar aqui variáveis globais, como a URL base, se desejar.
$url_base = '/Spark-main/'; 
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">


    <link rel="stylesheet" href="<?php echo $url_base; ?>css/style.css"> 

    <title><?php echo isset($page_title) ? $page_title : 'Spark App'; ?></title>
</head>
<body>