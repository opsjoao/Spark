// ==========================================================
// FUNÇÕES DE MENU (Correção do botão de 3 pontos)
// ==========================================================

function toggleReviewMenu(btn) {
    // Pega o container pai do botão clicado
    const container = btn.closest('.review-menu-container');
    
    // Fecha todos os outros menus que estejam abertos
    document.querySelectorAll('.review-menu-container.active').forEach(el => {
        if (el !== container) {
            el.classList.remove('active');
        }
    });

    // Alterna a classe 'active' no container atual
    // O CSS exibe o menu quando o container tem essa classe
    if (container) {
        container.classList.toggle('active');
    }
}

// Fecha o menu se clicar fora dele
document.addEventListener('click', function(event) {
    // Se o clique NÃO foi dentro de um container de menu
    if (!event.target.closest('.review-menu-container')) {
        // Fecha todos os menus
        document.querySelectorAll('.review-menu-container.active').forEach(el => {
            el.classList.remove('active');
        });
    }
});

// ==========================================================
// FUNÇÕES DE AVALIAÇÃO
// ==========================================================

function excluirAvaliacao(idAvaliacao) {
    if(!confirm("Tem certeza que deseja excluir sua avaliação?")) return;

    const formData = new FormData();
    formData.append('id_avaliacao', idAvaliacao);

    fetch('excluir_avaliacao.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            window.location.reload();
        } else {
            alert("Erro ao excluir: " + data.msg);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert("Erro de conexão ao tentar excluir.");
    });
}

function denunciarAvaliacao(id) {
    alert("Denúncia enviada para análise.");
}

// --- MODAL DE AVALIAÇÃO ---
function openEvaluationModal() {
    const modal = document.getElementById('evaluation-modal');
    if(modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeEvaluationModal() {
    const modal = document.getElementById('evaluation-modal');
    if(modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ==========================================================
// LÓGICAS DE CARREGAMENTO E UTILITÁRIOS
// ==========================================================

// Preview de imagem no modal
const imgInput = document.getElementById('imagemAvaliacao');
if(imgInput) {
    imgInput.addEventListener('change', function(e) {
        const container = document.getElementById('image-preview-container');
        container.innerHTML = '';
        if (this.files && this.files[0]) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(this.files[0]);
            container.appendChild(img);
        }
    });
}

// Lógica para Iniciar Evento (Contador e Ação)
function iniciarEvento(id) {
    alert("Iniciando evento...");
    // Aqui entraria a lógica real de iniciar
}

// Ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    // Lógica do Contador Regressivo (se existir o botão)
    const btnIniciar = document.getElementById('btnIniciarEvento');
    if (btnIniciar && btnIniciar.hasAttribute('data-starttime-ms')) {
        const eventoStartTime = parseInt(btnIniciar.dataset.starttimeMs, 10);

        if (!isNaN(eventoStartTime)) {
            const timerInterval = setInterval(function() {
                const agora = new Date().getTime();
                const distancia = eventoStartTime - agora;

                if (distancia <= 0) {
                    clearInterval(timerInterval);
                    window.location.reload(); // Recarrega para liberar o botão
                } else {
                    const dias = Math.floor(distancia / (1000 * 60 * 60 * 24));
                    const horas = Math.floor((distancia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutos = Math.floor((distancia % (1000 * 60 * 60)) / (1000 * 60));
                    const segundos = Math.floor((distancia % (1000 * 60)) / 1000);
                    
                    let timerText = "Inicia em: ";
                    if (dias > 0) timerText += dias + "d ";
                    timerText += [horas, minutos, segundos].map(u => u.toString().padStart(2, '0')).join(':');
                    
                    btnIniciar.textContent = timerText;
                }
            }, 1000);
        }
    }

    // Rolar até comentário destacado (se vier da página 'Minhas Avaliações')
    if (window.location.hash) {
        const el = document.querySelector(window.location.hash);
        if (el) {
            setTimeout(() => {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.classList.add('highlight'); // Adicione CSS para .highlight se quiser um efeito visual
            }, 500);
        }
    }
});
