// Aguarda o carregamento do HTML
document.addEventListener('DOMContentLoaded', function() {

  // --- LÓGICA DO MENU DE 3 PONTOS (código existente) ---
  const mainContent = document.querySelector('main');
  if (mainContent) {
    mainContent.addEventListener('click', function(event) {
      const toggle = event.target.closest('.post-menu-toggle');
      const denunciarBtn = event.target.closest('.denunciar-btn');
      const ocultarBtn = event.target.closest('.ocultar-btn');

      if (toggle) {
        const menu = toggle.nextElementSibling;
        document.querySelectorAll('.dropdown-menu.show').forEach(otherMenu => {
          if (otherMenu !== menu) {
            otherMenu.classList.remove('show');
          }
        });
        menu.classList.toggle('show');
        event.stopPropagation();
      }

      if (denunciarBtn) {
        event.preventDefault();
        alert("Agradecemos a sua denúncia. Nossos moderadores irão verificar possíveis irregularidades nessa publicação.");
        const menu = denunciarBtn.closest('.dropdown-menu');
        if (menu) { menu.classList.remove('show'); }
      }

      if (ocultarBtn) {
        event.preventDefault();
        const postParaOcultar = ocultarBtn.closest('.post');
        if (postParaOcultar) { postParaOcultar.style.display = 'none'; }
      }
    });
  }

  window.addEventListener('click', function(event) {
    if (!event.target.closest('.post-menu')) {
      document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
        menu.classList.remove('show');
      });
    }
  });


  // --- NOVO CÓDIGO: LÓGICA PARA ARRASTAR O CARROSSEL NO DESKTOP ---
  const carousel = document.querySelector('.carousel-container');
  if (carousel) {
    let isDown = false;
    let startX;
    let scrollLeft;

    carousel.addEventListener('mousedown', (e) => {
      isDown = true;
      carousel.classList.add('active-drag');
      startX = e.pageX - carousel.offsetLeft;
      scrollLeft = carousel.scrollLeft;
    });

    carousel.addEventListener('mouseleave', () => {
      isDown = false;
      carousel.classList.remove('active-drag');
    });

    carousel.addEventListener('mouseup', () => {
      isDown = false;
      carousel.classList.remove('active-drag');
    });

    carousel.addEventListener('mousemove', (e) => {
      if (!isDown) return;
      e.preventDefault();
      const x = e.pageX - carousel.offsetLeft;
      const walk = (x - startX) * 2; // O multiplicador *2 acelera o arraste
      carousel.scrollLeft = scrollLeft - walk;
    });
  }
  
});