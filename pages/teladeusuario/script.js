// Seleciona as duas "telas" principais da página
const screenSettings = document.getElementById('screen-settings');
const screenEdit = document.getElementById('screen-edit');

// Função para ir para a tela de edição
function goToEdit() {
    screenSettings.classList.remove('active'); // Esconde a tela de visualização
    screenEdit.classList.add('active');      // Mostra a tela de edição
}

// Função para voltar para a tela de visualização
function goBack() {
    screenEdit.classList.remove('active');      // Esconde a tela de edição
    screenSettings.classList.add('active'); // Mostra a tela de visualização
}

// Função para salvar o perfil (ainda não implementada no back-end)
function saveProfile() {
    // Aqui iria a lógica para pegar os dados dos inputs e enviar para um script PHP
    alert("Funcionalidade de salvar ainda não implementada.");
    goBack(); // Volta para a tela de visualização após o alerta
}