// ==========================================================
// FUNÇÕES GLOBAIS (acessíveis pelo HTML via onclick)
// ==========================================================

// Função para abrir o pop-up (modal) de avaliação
function openEvaluationModal() {
    const modal = document.getElementById('evaluation-modal');
    if (modal) {
        modal.style.display = 'flex';
    }
}

// Função para fechar o pop-up (modal) de avaliação
function closeEvaluationModal() {
    const modal = document.getElementById('evaluation-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// Função que envia o comando para "iniciar" um evento
function iniciarEvento(idEvento) {
    const btn = document.getElementById('btnIniciarEvento');
    if (!btn) return;

    btn.textContent = 'Iniciando...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('idEvento', idEvento);

    // Envia os dados para o script PHP em segundo plano
    fetch('iniciar_evento.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Se o PHP responder com sucesso, recarrega a página para mostrar o novo botão "Finalizar Evento"
            window.location.reload();
        } else {
            alert('Erro: ' + (data.message || 'Não foi possível iniciar o evento.'));
            btn.textContent = 'Tente Novamente';
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erro de conexão:', error);
        alert('Ocorreu um erro de conexão.');
    });
}


// ==========================================================
// LÓGICAS QUE RODAM APÓS O CARREGAMENTO DA PÁGINA
// ==========================================================
document.addEventListener('DOMContentLoaded', function() {

    // --- Lógica do Contador Regressivo ---
    const btnIniciar = document.getElementById('btnIniciarEvento');
    if (btnIniciar && btnIniciar.hasAttribute('data-starttime-ms')) {
        
        const eventoStartTime = parseInt(btnIniciar.dataset.starttimeMs, 10);

        if (isNaN(eventoStartTime)) {
            btnIniciar.textContent = "Data inválida";
            return;
        }

        const timerInterval = setInterval(function() {
            const agora = new Date().getTime();
            const distancia = eventoStartTime - agora;

            if (distancia <= 0) {
                clearInterval(timerInterval);
                // O tempo acabou, recarrega a página para o PHP mostrar o botão "Iniciar Evento"
                window.location.reload();
            } else {
                const dias = Math.floor(distancia / (1000 * 60 * 60 * 24));
                const horas = Math.floor((distancia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutos = Math.floor((distancia % (1000 * 60 * 60)) / (1000 * 60));
                const segundos = Math.floor((distancia % (1000 * 60)) / 1000);
                
                let timerText = "Começa em: ";
                if (dias > 0) timerText += dias + "d ";
                timerText += horas.toString().padStart(2, '0') + ":" + 
                             minutos.toString().padStart(2, '0') + ":" + 
                             segundos.toString().padStart(2, '0');
                btnIniciar.textContent = timerText;
            }
        }, 1000);
    }

    // --- Lógica da Pré-visualização da Imagem no Modal ---
    const fileInput = document.getElementById('imagemAvaliacao');
    const previewContainer = document.getElementById('image-preview-container');
    
    if (fileInput && previewContainer) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('image-preview');
                    const removeBtn = document.createElement('button');
                    removeBtn.type = "button";
                    removeBtn.textContent = 'Remover Foto';
                    removeBtn.classList.add('remove-image-btn');
                    removeBtn.onclick = function() {
                        fileInput.value = '';
                        previewContainer.innerHTML = '';
                    };
                    previewContainer.appendChild(img);
                    previewContainer.appendChild(removeBtn);
                }
                reader.readAsDataURL(file);
            } else {
                previewContainer.innerHTML = '';
            }
        });
    }

    // --- Lógica para Rolar até um Comentário Destacado ---
    const params = new URLSearchParams(window.location.search);
    const highlightId = params.get('highlight');

    if (highlightId) {
        const commentElement = document.getElementById('avaliacao-' + highlightId);
        if (commentElement) {
            // Rola a tela até o comentário de forma suave
            commentElement.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Adiciona uma classe de destaque temporária
            commentElement.classList.add('highlight');

            // Remove a classe de destaque após alguns segundos
            setTimeout(() => {
                commentElement.classList.remove('highlight');
            }, 2500); // 2.5 segundos
        }
    }
});