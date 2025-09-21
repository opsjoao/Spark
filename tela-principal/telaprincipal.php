<?php require_once('../formulario-login/verificacao.php'); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Spark</title>
  
  <link rel="stylesheet" href="telaprincipal.css">
  
  <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>
<body>
  <header class="topbar">
    <i class='bx bx-menu'></i>
    <h1>Procure por um parque</h1>
    <i class='bx bx-search' onclick="window.location.href='../telas-mapas/index.html'"></i>
  </header>

  <nav class="sidebar">
    <a href="#" class="active"><i class='bx bx-home'></i><span>Início</span></a>
    <a href="../tela-atividades/atividades.php"><i class='bx bx-run'></i><span>Atividades</span></a>
    <a href="../TelaPerfils/perfil.html"><i class='bx bx-user'></i><span>Amigos</span></a>
    <a href="#"><i class='bx bx-star'></i><span>Favoritos</span></a>
    <a href="../teladeusuario/teladeusuario.php"><i class='bx bx-cog'></i><span>Conta</span></a>
  </nav>

  <main>
    <section class="destaque">
      <h2>Atividades em Destaque</h2>
      <div class="carousel-container">
        <div class="carousel-item" onclick="window.location.href='../tela-evento/tela-evento.html'">
          <img src="capoeira.jpg" alt="Atividade de Capoeira">
          <div class="carousel-item-text">Ginástica no Sapão-Ratão</div>
        </div>
        <div class="carousel-item">
          <img src="https://vejasp.abril.com.br/wp-content/uploads/2023/04/Show-do-cantor-seu-jorge-no-parque-ibirapuera-credito-bruno-muti-01.jpg" alt="Show no Parque Ibirapuera">
          <div class="carousel-item-text">Show no Parque Ibirapuera</div>
        </div>
        <div class="carousel-item">
          <img src="https://media.static-allgather.com/MundoNipo/Upload/Images/Posts/750x422/1155_20170119131653.jpg" alt="Feira na Liberdade">
          <div class="carousel-item-text">Festival das Estrelas na Liberdade</div>
        </div>
        </div>
    </section>
    
    <section class="post" id="post-1">
      <div class="post-header">
        <img src="imagem-perfil-matheus.jpg" alt="user" class="avatar">
        <div class="user-info">
          <h3>Matheusshalom23</h3>
          <p>@theuzin23</p>
        </div>
        
        <div class="post-menu">
          <i class='bx bx-dots-vertical-rounded post-menu-toggle'></i>
          <div class="dropdown-menu">
            <a href="#" class="dropdown-item denunciar-btn">Denunciar</a>
            <a href="#" class="dropdown-item ocultar-btn">Ocultar</a>
          </div>
        </div>
      </div>
      
      <img src="gabiru.jpg" alt="Parque" class="post-img">
      
      <div class="post-body">
        <div class="post-title-line">
          <div class="title-and-location">
            <h4>Festival Cerejeiras</h4>
            <p class="local">Parque do Carmo</p>
          </div>
          <p class="stars">
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-solid fa-star"></i>
            <i class="fa-regular fa-star"></i>
          </p>
        </div>
        <p>Muito bom, amei esse parque, gostei muito quando um gabríu subiu na minha perna!!!</p>
      </div>
    </section>
  </main>

<nav class="bottombar">
    <button class="nav-btn" onclick="window.location.href='../TelaPerfils/perfil.html'">
      <i class="fa-solid fa-users"></i>
    </button>
    <button class="nav-btn" onclick="window.location.href='../tela-atividades/atividades.php'">
      <i class="fa-solid fa-person-walking"></i>
    </button>
    <button class="nav-btn active" onclick="window.location.href='../tela-principal/telaprincipal.php'">
      <i class="fa-solid fa-house"></i>
    </button>
    <button class="nav-btn" onclick="window.location.href='../telaFavoritos/index.html'">
      <i class="fa-solid fa-star"></i>
    </button>
    <button class="nav-btn" onclick="window.location.href='../teladeusuario/teladeusuario.php'">
      <i class="fa-solid fa-user"></i>
    </button>
  </nav>

  <script src="telaprincipal.js"></script>
</body>

</html>
