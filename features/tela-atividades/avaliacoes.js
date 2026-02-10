// CONTEÚDO DO ARQUIVO avaliacoes.js (ESTRUTURA REVISADA)

document.addEventListener('DOMContentLoaded', (event) => {

    // --- Variáveis de elementos DOM ---
    // Coloque as definições de const/let DENTRO deste bloco
    const estrelas = document.querySelectorAll('input[name="rate"]');
    const displayDiv = document.getElementById('valor-selecionado');
    const submitButton = document.getElementById('submit');
    const savedDisplayDiv = document.getElementById('avaliacao-salva');

    // Variáveis para Categorias
    // VERIFIQUE SE ESTE SELETOR ENCONTRA OS BOTÕES
    const categoryButtons = document.querySelectorAll('.category-button');
    let categoriasSelecionadas = []; 
    let valorAtual = null; 

    // DEBUG: Se esta linha não aparecer no seu Console (F12), o problema é no carregamento do script.
    console.log("Script 'avaliacoes.js' carregado. Encontrados " + categoryButtons.length + " botões de categoria.");
    // --------------------------------------------------------------------------------------------------


    // --- Funções de Manipulação do Array no localStorage (Mantidas) ---
    function getTodasAvaliacoes() {
        // ... (código mantido) ...
        const avaliacoesJSON = localStorage.getItem('todasAvaliacoesComDetalhes'); 
        try {
            return avaliacoesJSON ? JSON.parse(avaliacoesJSON) : [];
        } catch (e) {
            console.error("Erro ao parsear avaliações do LocalStorage:", e);
            return [];
        }
    }

    function saveTodasAvaliacoes(avaliacoes) {
        localStorage.setItem('todasAvaliacoesComDetalhes', JSON.stringify(avaliacoes)); 
    }


    // --- Lógica de Seleção de Categoria (AQUI ESTÁ O EVENT LISTENER) ---
    categoryButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.getAttribute('data-category');
            
            // Alterna a classe 'selected'
            this.classList.toggle('selected'); // <-- Esta linha adiciona a classe!

            if (this.classList.contains('selected')) {
                // Adiciona se foi selecionado
                if (!categoriasSelecionadas.includes(category)) {
                    categoriasSelecionadas.push(category);
                }
            } else {
                // Remove se foi deselecionado
                categoriasSelecionadas = categoriasSelecionadas.filter(cat => cat !== category);
            }
        });
    });


    // --- Função para calcular, contar e exibir a Média e o Detalhe (Mantida) ---
    function calcularEMostrarDetalhes() {
        // ... (código de cálculo e exibição mantido) ...
        const todasAvaliacoes = getTodasAvaliacoes();
        const totalAvaliacoes = todasAvaliacoes.length;

        if (totalAvaliacoes === 0) {
            savedDisplayDiv.innerHTML = 'Ainda não há avaliações salvas.';
            return;
        }

        let somaTotal = 0;
        const contagemNotas = { "1": 0, "2": 0, "3": 0, "4": 0, "5": 0 };
        const contagemCategorias = {}; 

        todasAvaliacoes.forEach(avaliacao => {
            const nota = parseInt(avaliacao.nota, 10); 
            somaTotal += nota;
            if (contagemNotas[avaliacao.nota] !== undefined) {
                contagemNotas[avaliacao.nota]++;
            }
            
            if (avaliacao.categorias && Array.isArray(avaliacao.categorias) && avaliacao.categorias.length > 0) {
                avaliacao.categorias.forEach(categoria => {
                    contagemCategorias[categoria] = (contagemCategorias[categoria] || 0) + 1;
                });
            }
        });

        const media = somaTotal / totalAvaliacoes;
        
        // ... (Resto do HTML de exibição de nota e categoria) ...
        let detalheNotaHtml = '<h3>Contagem por Nota:</h3><ul>';
        for (const nota in contagemNotas) {
            detalheNotaHtml += `<li><strong>${nota} estrela(s):</strong> ${contagemNotas[nota]} voto(s)</li>`;
        }
        detalheNotaHtml += '</ul>';

        let detalheCategoriaHtml = '<h3>Foco da Avaliação (Menções):</h3><ul>';
        const categoriasOrdenadas = Object.entries(contagemCategorias).sort(([, a], [, b]) => b - a); 
        
        if (categoriasOrdenadas.length > 0) {
            categoriasOrdenadas.forEach(([categoria, contagem]) => {
                detalheCategoriaHtml += `<li><strong>${categoria}:</strong> ${contagem} menção(ões)</li>`;
            });
        } else {
            detalheCategoriaHtml += '<li>Nenhuma categoria foi mencionada ainda.</li>';
        }
        detalheCategoriaHtml += '</ul>';


        savedDisplayDiv.innerHTML = `
            <h2>Resultado Geral:</h2>
            <p>Total de Avaliações Registradas: <strong>${totalAvaliacoes}</strong></p>
            <p>Média Geral: <strong>${media.toFixed(2)}</strong> estrelas</p>
            <hr>
            ${detalheNotaHtml}
            <hr>
            ${detalheCategoriaHtml}
        `;
    }

    // --- 1. Lógica de SELEÇÃO Estrelas ---
    estrelas.forEach(estrela => {
        estrela.addEventListener('change', function() {
            valorAtual = this.value; 
            displayDiv.textContent = 'Você selecionou: ' + valorAtual + ' estrela(s).';
        });
    });

    // --- 2. Lógica de SALVAR ---
    submitButton.addEventListener('click', function() {
        if (valorAtual) {
            const novaAvaliacao = {
                nota: valorAtual,
                categorias: categoriasSelecionadas 
            };

            const avaliacoes = getTodasAvaliacoes();
            avaliacoes.push(novaAvaliacao);
            saveTodasAvaliacoes(avaliacoes);
            
            // Limpa o estado após salvar
            valorAtual = null;
            categoriasSelecionadas = [];
            displayDiv.textContent = 'Selecione uma avaliação.';

            // Desmarca visualmente
            // --- 2. Lógica de SALVAR ---
submitButton.addEventListener('click', function() {
    if (valorAtual) {
        // ... (código para salvar a avaliação) ...

        // Limpa o estado após salvar
        valorAtual = null;
        categoriasSelecionadas = [];
        displayDiv.textContent = 'Selecione uma avaliação.';

        // VULNERÁVEL: document.querySelector('input[name="rate"]:checked')?.checked = false;
        
        // CORREÇÃO: Verifique se o elemento existe antes de desmarcar
        const estrelaChecada = document.querySelector('input[name="rate"]:checked');
        if (estrelaChecada) {
            estrelaChecada.checked = false;
        }
        
        // Desmarca visualmente os botões de categoria
        categoryButtons.forEach(button => button.classList.remove('selected'));

        // Atualiza a exibição
        calcularEMostrarDetalhes(); 

        alert(`Avaliação de ${novaAvaliacao.nota} estrela(s) salva!`);
    } else {
        alert('Por favor, selecione uma estrela antes de salvar.');
    }
});
            categoryButtons.forEach(button => button.classList.remove('selected'));

            // Atualiza a exibição
            calcularEMostrarDetalhes(); 

            alert(`Avaliação de ${novaAvaliacao.nota} estrela(s) salva!`);
        } else {
            alert('Por favor, selecione uma estrela antes de salvar.');
        }
    });

    // 3. Chamamos a função ao carregar a página
    calcularEMostrarDetalhes();

}); // Fim do DOMContentLoaded
