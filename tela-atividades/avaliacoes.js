
    // --- Variáveis de elementos DOM (Mantidas do exemplo anterior) ---
    const estrelas = document.querySelectorAll('input[name="rate"]');
    const displayDiv = document.getElementById('valor-selecionado');
    const submitButton = document.getElementById('submit');
    const savedDisplayDiv = document.getElementById('avaliacao-salva');

    let valorAtual = null; 

    // --- Funções de Manipulação do Array no localStorage ---

    function getTodasAvaliacoes() {
        const avaliacoesJSON = localStorage.getItem('todasAvaliacoes');
        return avaliacoesJSON ? JSON.parse(avaliacoesJSON) : [];
    }

    function saveTodasAvaliacoes(avaliacoes) {
        localStorage.setItem('todasAvaliacoes', JSON.stringify(avaliacoes));
    }

    // --- NOVO: Função para calcular, contar e exibir a Média e o Detalhe das Notas ---
    function calcularEMostrarDetalhes() {
        const todasAvaliacoes = getTodasAvaliacoes();
        const totalAvaliacoes = todasAvaliacoes.length;

        if (totalAvaliacoes === 0) {
            savedDisplayDiv.innerHTML = 'Ainda não há avaliações salvas.';
            return;
        }

        // Variáveis de Cálculo
        let somaTotal = 0;
        
        // Objeto para Contagem: { "1": 0, "2": 0, "3": 0, "4": 0, "5": 0 }
        const contagemNotas = { "1": 0, "2": 0, "3": 0, "4": 0, "5": 0 };

        // Itera sobre todas as avaliações para somar e contar
        todasAvaliacoes.forEach(avaliacao => {
            const nota = parseInt(avaliacao, 10); // Converte a string para número
            
            // 1. Soma para calcular a Média
            somaTotal += nota;
            
            // 2. Conta a Ocorrência da Nota
            // Usa a nota (como string) para incrementar o contador no objeto
            if (contagemNotas[avaliacao] !== undefined) {
                 contagemNotas[avaliacao]++;
            }
        });

        // 3. Calcula a Média
        const media = somaTotal / totalAvaliacoes;
        
        // 4. Cria o HTML para exibir a contagem detalhada
        let detalheHtml = '<h3>Contagem por Nota:</h3><ul>';
        for (const nota in contagemNotas) {
            detalheHtml += `<li><strong>${nota} estrela(s):</strong> ${contagemNotas[nota]} voto(s)</li>`;
        }
        detalheHtml += '</ul>';

        // 5. Exibe o resultado final com a média e o detalhe
        savedDisplayDiv.innerHTML = `
            <h2>Resultado Geral:</h2>
            <p>Total de Avaliações Registradas: <strong>${totalAvaliacoes}</strong></p>
            <p>Média Geral: <strong>${media.toFixed(2)}</strong> estrelas</p>
            <hr>
            ${detalheHtml}
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
            const avaliacoes = getTodasAvaliacoes();
            avaliacoes.push(valorAtual);
            saveTodasAvaliacoes(avaliacoes);
            
            // Chama a nova função de exibição
            calcularEMostrarDetalhes(); 

            alert('Avaliação de ' + valorAtual + ' estrela(s) salva e resultados atualizados!');
        } else {
            alert('Por favor, selecione uma estrela antes de salvar.');
        }
    });

    // 3. Chamamos a função ao carregar a página para mostrar os resultados atuais
    calcularEMostrarDetalhes(); 
