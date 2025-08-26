// Obtém todos os inputs com o mesmo nome "exibirDias"
const radioButtons = document.querySelectorAll('input[name="evento"]');

// Obtém o elemento que contém a lista de dias
const diasDaSemanaDiv = document.getElementById('diasDaSemana');

// Adiciona um "ouvinte de evento" (event listener) a cada input de rádio
radioButtons.forEach(radio => {
    radio.addEventListener('change', function() {
        // Verifica qual rádio está selecionado (checked)
        if (this.value === 'Semanal') {
            // Se for "sim", mostra a div de dias
            diasDaSemanaDiv.style.display = 'block';
        } else {
            // Se for "não" (ou qualquer outra coisa), esconde a div
            diasDaSemanaDiv.style.display = 'none';
        }
    });
});
