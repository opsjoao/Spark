    // --- Variáveis de elementos DOM ---
    const estrelas = document.querySelectorAll('input[name="rate"]');
    const displayDiv = document.getElementById('valor-selecionado');
    const submitButton = document.getElementById('submit');
    const savedDisplayDiv = document.getElementById('avaliacao-salva'); // Vamos reutilizar esta div

    let valorAtual = null; // Variável para rastrear o valor selecionado

    // --- Funções de Manipulação do Array no localStorage ---

    // Função para obter o array de avaliações do localStorage
    function getTodasAvaliacoes() {
        const avaliacoesJSON = localStorage.getItem('todasAvaliacoes');
        // Se houver dados, retorna o array. Se não, retorna um array vazio.
        return avaliacoesJSON ? JSON.parse(avaliacoesJSON) : [];
    }

    // Função para salvar o array de avaliações no localStorage
    function saveTodasAvaliacoes(avaliacoes) {
        localStorage.setItem('todasAvaliacoes', JSON.stringify(avaliacoes));
    }

    // Função principal para calcular e exibir a Média
    function calcularEMostrarMedia() {
        const todasAvaliacoes = getTodasAvaliacoes();
        const totalAvaliacoes = todasAvaliacoes.length;

        if (totalAvaliacoes === 0) {
            savedDisplayDiv.innerHTML = 'Ainda não há avaliações salvas para calcular a média.';
            return;
        }

        // 1. Somar todas as avaliações
        // Usamos reduce para somar. O valor inicial da soma é 0.
        const somaTotal = todasAvaliacoes.reduce((soma, avaliacao) => {
            // Os valores vêm como string, então precisamos converter para número (parseInt)
            return soma + parseInt(avaliacao, 10); 
        }, 0);

        // 2. Calcular a média
        const media = somaTotal / totalAvaliacoes;

        // 3. Exibir o resultado
        savedDisplayDiv.innerHTML = `
            Total de Avaliações Salvas: <strong>${totalAvaliacoes}</strong><br>
            Soma Total dos Valores: <strong>${somaTotal}</strong><br>
            Média das Avaliações: <strong>${media.toFixed(2)}</strong> estrelas.
        `;
    }


    // --- 1. Lógica de SELEÇÃO (Feedback imediato) ---
    estrelas.forEach(estrela => {
        estrela.addEventListener('change', function() {
            valorAtual = this.value; 
            displayDiv.textContent = 'Você selecionou: ' + valorAtual + ' estrela(s).';
        });
    });

    // --- 2. Lógica de SALVAR (Ao clicar no botão) ---
    submitButton.addEventListener('click', function() {
        if (valorAtual) {
            // 1. Pega a lista atual
            const avaliacoes = getTodasAvaliacoes();
            
            // 2. Adiciona a nova avaliação
            avaliacoes.push(valorAtual);
            
            // 3. Salva a lista atualizada
            saveTodasAvaliacoes(avaliacoes);
            
            // 4. Recalcula e mostra a nova média
            calcularEMostrarMedia();

            alert('Avaliação de ' + valorAtual + ' estrela(s) salva e média atualizada!');
            
            // Opcional: Desmarca a estrela após salvar, se desejar
            // document.querySelector('input[name="rate"]:checked').checked = false;
            // displayDiv.textContent = 'Selecione uma avaliação.';

        } else {
            alert('Por favor, selecione uma estrela antes de salvar.');
        }
    });

    // 4. Chamamos a função ao carregar a página para mostrar a média atual
    calcularEMostrarMedia(); 
