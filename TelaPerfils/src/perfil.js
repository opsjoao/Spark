// Função para trocar as abas
function showTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
    document.getElementById(tabName).classList.add('active');
    document.querySelector(`.tab-button[onclick="showTab('${tabName}')"]`).classList.add('active');
}

// Função para responder a uma solicitação (Aceitar/Recusar)
function responderSolicitacao(idSolicitante, acao) {
    fetch('responder_solicitacao.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ idSolicitante: idSolicitante, acao: acao })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(acao === 'aceitar' ? 'Amigo adicionado!' : 'Solicitação recusada.');
            window.location.reload(); // Recarrega a página para atualizar as listas
        } else {
            alert('Ocorreu um erro.');
        }
    });
}

// Adiciona a lógica de busca na página adicionar_amigo.php
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-input');
    const searchButton = document.getElementById('search-button');
    const resultsContainer = document.getElementById('search-results-container');

    if (searchButton) {
        searchButton.addEventListener('click', buscar);
        searchInput.addEventListener('keyup', (e) => {
            if (e.key === 'Enter') buscar();
        });
    }
    
    function buscar() {
        const query = searchInput.value;
        if (query.length < 2) return;
        
        fetch(`buscar_usuarios.php?q=${query}`)
        .then(response => response.json())
        .then(data => {
            resultsContainer.innerHTML = '';
            if (data.length > 0) {
                data.forEach(user => {
                    const avatar = user.avatar_path ? `/Spark-main/${user.avatar_path}` : '/Spark-main/assets/images/avatar_padrao.png';
                    resultsContainer.innerHTML += `
                        <div class="friend-card search-result">
                            <img src="${avatar}" alt="Avatar" class="avatar">
                            <div class="user-info">
                                <h3>${user.nome}</h3>
                                <p>@${user.username}</p>
                            </div>
                            <button class="btn-add" onclick="enviarSolicitacao(${user.idUsuario}, this)">
                                <i class="fa-solid fa-user-plus"></i>
                            </button>
                        </div>
                    `;
                });
            } else {
                resultsContainer.innerHTML = '<p class="empty-message">Nenhum usuário encontrado.</p>';
            }
        });
    }
});

// Função para enviar uma solicitação de amizade
function enviarSolicitacao(idAmigo, buttonElement) {
    fetch('enviar_solicitacao.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ idAmigo: idAmigo })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            buttonElement.innerHTML = '<i class="fa-solid fa-check"></i>';
            buttonElement.disabled = true;
        } else {
            alert('Erro: ' + data.message);
        }
    });
}