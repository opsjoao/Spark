let map, markers = [], autocomplete, placesService, infoWindow;

// Variáveis de controle de arrasto
let isDragging = false;
let startY = 0;
let initialTranslateY = 0;
// NOVA VARIÁVEL: Rastreia se o movimento do mouse/toque foi um ARRASTO significativo.
let wasDragged = false; 
const DRAG_THRESHOLD = 5; // Mínimo de pixels para ser considerado "arrasto"
let detailsContainer = document.getElementById("park-details"); 


function initMap() {
    const fallbackLocal = { lat: -23.5505, lng: -46.6333 }; // SP

    map = new google.maps.Map(document.getElementById("map"), {
        center: fallbackLocal,
        zoom: 13
    });

    detailsContainer = document.getElementById("park-details");
    setupDragEvents(); // Configura os eventos de arrasto
    setupClickToClose(); // NOVO: Configura o evento de clique fora

    placesService = new google.maps.places.PlacesService(map);
    infoWindow = new google.maps.InfoWindow();

    const input = document.getElementById("pac-input");
    autocomplete = new google.maps.places.Autocomplete(input, {
        fields: ['name','geometry','types','formatted_address','place_id']
    });
    autocomplete.bindTo("bounds", map);

    autocomplete.addListener("place_changed", () => {
        const place = autocomplete.getPlace();
        if (!place.geometry || !place.geometry.location) {
            alert("Selecione uma opção.");
            return;
        }

        clearMarkers();
        hideDetailsPane(); 

        if (place.types && place.types.includes("park")) {
            addMarker(place.geometry.location, place.name, place.place_id);
            map.setCenter(place.geometry.location);
            map.setZoom(15);
        } else {
            buscarParquesProximos(place.geometry.location);
        }
    });

    // pega o GPS
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const userPos = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                map.setCenter(userPos);
                addMarkerUser(userPos, "Você está aqui!");
                buscarParquesProximos(userPos);
            },
            () => {
                console.warn("GPS não permitido. Usando localização padrão.");
                buscarParquesProximos(fallbackLocal);
            }
        );
    } else {
        buscarParquesProximos(fallbackLocal);
    }
}

function buscarParquesProximos(localizacao) {
    const raio = document.getElementById("radiusInput").value * 1000; // km para metros
    const request = {
        location: localizacao,
        radius: raio,
        type: 'park'
    };

    placesService.nearbySearch(request, (results, status) => {
        if (status === google.maps.places.PlacesServiceStatus.OK && results.length) {
            const bounds = new google.maps.LatLngBounds();
            results.forEach(r => {
                addMarker(r.geometry.location, r.name, r.place_id);
                bounds.extend(r.geometry.location);
            });
            map.fitBounds(bounds);
        } else {
            alert("Nenhum parque encontrado nessa região.");
        }
    });
}

function addMarker(position, title, placeId) {
    const m = new google.maps.Marker({
        map,
        position,
        title,
        icon: {
            url: "src/assets/pin_verde.png",
            scaledSize: new google.maps.Size(20, 30)
        }
    });

    if (placeId) {
        m.addListener("click", () => {
            showDetailsPane(placeId, title);
        });
    }

    markers.push(m);
}

// ----------------------------------------------------
// Lógica de Arrastar e Esconder (Drag-to-Dismiss)
// ----------------------------------------------------

function hideDetailsPane() {
    // Esconde o painel e reseta o transform para a próxima vez
    if (detailsContainer && detailsContainer.style.display !== 'none') {
        // Usa transform para animar para fora da tela
        detailsContainer.style.transition = 'transform 0.3s ease-in';
        detailsContainer.style.transform = `translateY(${detailsContainer.offsetHeight + 20}px)`;

        // Oculta completamente após a animação
        setTimeout(() => {
            detailsContainer.style.display = 'none';
            detailsContainer.style.transform = 'translateY(0)'; // Volta à posição inicial invisível
            detailsContainer.style.transition = 'none'; // Desliga a transição ao esconder
        }, 300);
    }
}

function setupDragEvents() {
    if (!detailsContainer) return;
    
    // Certifique-se de que os ouvintes de evento estejam anexados ao detailsContainer
    detailsContainer.addEventListener('mousedown', startDrag);
    detailsContainer.addEventListener('touchstart', startDrag);
    
    // Os eventos de movimento e soltura devem ser no document para capturar mesmo que o mouse/dedo saia do container
    document.addEventListener('mousemove', drag);
    document.addEventListener('touchmove', drag);
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchend', endDrag);
}

// ... (código anterior) ...

function startDrag(event) {
    // Pega o ponto de contato inicial (mouse ou toque)
    const initialY = event.type.startsWith('touch') ? event.touches[0].clientY : event.clientY;

    // Se o conteúdo interno estiver rolado (e houver mais conteúdo para rolar),
    // NÃO iniciamos o arrasto para fechar. Isso permite a rolagem interna normal.
    // Só permite o arrasto se o scroll estiver no topo (0) E houver scrollbar.
    if (detailsContainer.scrollHeight > detailsContainer.clientHeight && detailsContainer.scrollTop > 0) {
        return; 
    }
    
    // Se a rolagem estiver no topo, iniciamos o arrasto
    isDragging = true;
    wasDragged = false; 
    startY = initialY;
    
    // Desliga a transição para que o arrasto seja imediato
    detailsContainer.style.transition = 'none'; 
    detailsContainer.style.cursor = 'grabbing';
    
    // Calcula a translação inicial (deve ser 0 se estiver visível no topo)
    const style = window.getComputedStyle(detailsContainer);
    const transform = style.getPropertyValue('transform');
    const match = transform.match(/matrix\(1, 0, 0, 1, 0, (.*)\)/);
    initialTranslateY = match ? parseFloat(match[1]) : 0;
}

// ... (código continua) ...

// ... (código anterior) ...

function drag(event) {
    if (!isDragging) {
        // Se o arrasto não foi iniciado (porque a rolagem interna estava ativa),
        // permitimos o comportamento padrão (rolagem).
        return; 
    }

    const currentY = event.type.startsWith('touch') ? event.touches[0].clientY : event.clientY;
    let diffY = currentY - startY;
    
    // --- LÓGICA DE PRIORIDADE DE ROLAGEM ---
    // 1. Se o usuário está tentando rolar para CIMA (diffY < 0),
    //    e há conteúdo abaixo da tela (detailsContainer.scrollTop > 0), 
    //    devemos permitir a rolagem nativa para cima. 
    //    Para isso, NÂO chamamos event.preventDefault() e simplesmente retornamos.
    if (detailsContainer.scrollTop > 0 && diffY < 0) {
        // Isso permite que o navegador trate o movimento como rolagem interna
        isDragging = false; // Desliga o arrasto para não interferir no próximo toque
        return; 
    }
    // ---------------------------------------

    // Se o usuário está aqui, está no topo (scrollTop == 0) e arrastando para baixo.
    event.preventDefault(); // Previne o scroll da página, pois estamos no modo 'arrastar'
    
    // Se o movimento vertical for maior que o limite, marca como arrasto
    if (Math.abs(currentY - startY) > DRAG_THRESHOLD) {
        wasDragged = true;
    }

    // Garante que só se pode arrastar para baixo (diffY >= 0)
    if (diffY < 0) {
        diffY = 0;
    }

    // Move o painel aplicando a translação Y
    detailsContainer.style.transform = `translateY(${initialTranslateY + diffY}px)`;
}

// ... (código restante da função endDrag e outras) ...
function endDrag(event) {
    if (!isDragging) return;
    
    isDragging = false;
    detailsContainer.style.transition = 'transform 0.3s ease-out'; // Liga a transição novamente
    detailsContainer.style.cursor = 'grab';

    const style = window.getComputedStyle(detailsContainer);
    const transform = style.getPropertyValue('transform');
    const match = transform.match(/matrix\(1, 0, 0, 1, 0, (.*)\)/);
    const finalTranslateY = match ? parseFloat(match[1]) : 0;
    
    const threshold = detailsContainer.offsetHeight * 0.25; 

    if (finalTranslateY > threshold) {
        // Arrastou o suficiente: esconde com animação (chama hideDetailsPane)
        hideDetailsPane();
    } else {
        // Não arrastou o suficiente: retorna para a posição original
        detailsContainer.style.transform = 'translateY(0)';
    }
}

// ----------------------------------------------------
// NOVO: Função para manter o click fora fechando o painel
// ----------------------------------------------------
function setupClickToClose() {
    document.addEventListener('click', (event) => {
        // Se o movimento anterior foi um arrasto significativo, ignore este "clique".
        if (wasDragged) {
            wasDragged = false; // Reseta para o próximo clique
            return;
        }

        const isClickInsidePane = detailsContainer.contains(event.target);
        // Verifica se é o marcador (clicar no marcador deve manter o painel aberto)
        const isClickOnMarker = event.target.tagName === 'IMG' && event.target.src.includes('pin_verde.png'); 

        // Esconde o painel se estiver visível E o clique NÃO foi dentro do painel E NÃO foi no marcador
        if (detailsContainer.style.display === 'block' && !isClickInsidePane && !isClickOnMarker) {
            hideDetailsPane();
        }
    });
}

// ----------------------------------------------------
// Função showDetailsPane (Atualizada para o novo design)
// ----------------------------------------------------
function showDetailsPane(placeId, title) {
    const request = {
        // CAMPO 'editorial_summary' ADICIONADO AQUI 👇
        placeId: placeId,
        fields: ['name', 'formatted_address', 'website', 'formatted_phone_number', 'photos', 'opening_hours', 'rating', 'user_ratings_total', 'reviews', 'editorial_summary']
    };

    placesService.getDetails(request, (place, status) => {
        detailsContainer = document.getElementById("park-details"); 
        
        if (status === google.maps.places.PlacesServiceStatus.OK) {
            let photoUrl = "";
            if (place.photos && place.photos.length > 0) {
                // Tenta pegar a URL de uma foto maior para a capa
                photoUrl = place.photos[0].getUrl({ maxWidth: detailsContainer.clientWidth || 400 }); 
            }

            // Simulação de Dados Adicionais (conforme o print)
            const simulatedHours = place.opening_hours && place.opening_hours.open_now !== undefined ? 
                `06:00 - 23:00` : "Horário indisponível"; 
            
            // NOVO: Puxa o 'editorial_summary' real ou usa um fallback genérico
            const realAbout = place.editorial_summary ? 
                place.editorial_summary.overview : 
                "Informações detalhadas sobre este parque não estão disponíveis. No entanto, é um ótimo lugar para desfrutar da natureza e de atividades ao ar livre!";
            
            // 👇 SUBSTITUINDO O ANTIGO 'simulatedAbout'
            const parkDescription = realAbout;


            const simulatedParticipants = [
                { photo: "src/assets/avatar1.png" }, // Simulação de imagem do participante 1
                { photo: "src/assets/avatar2.png" }  // Simulação de imagem do participante 2
            ];
            const simulatedReview = {
                name: "Robert Renan",
                text: "O piquenique foi espetacular, as pessoas eram muito divertidas, e a comida era muito boa! Se tiver mais vozes, participem, pois vale muito a pena!",
                rating: 5
            };
            
            // Lógica para o link do site usando dados reais
            const websiteLink = place.website ? 
                `<i class="fa-solid fa-globe"></i> <a href="${place.website}" target="_blank" style="color:#7CBD64;">Site: ${place.website}</a>` :
                `<i class="fa-solid fa-globe"></i> <span>Site: Não disponível</span>`;

            // Função para renderizar estrelas
            const renderStars = (rating) => {
                let stars = '';
                for (let i = 1; i <= 5; i++) {
                    stars += `<i class="fa-solid fa-star" style="color: ${i <= rating ? 'gold' : '#ccc'};"></i>`;
                }
                return stars;
            };

            const content = `
                <div class="details-header">
                    <i class="fa-solid fa-arrow-left back-arrow" onclick="hideDetailsPane()"></i> 
                    <h2>Detalhes do parque</h2>
                </div>
                
                ${photoUrl ? `<img src="${photoUrl}" alt="${place.name}" class="park-image-cover">` : ''}

                <div class="park-content">
                    <h1 class="text-xl font-bold text-gray-800 mb-2">${place.name || title}</h1>
                    <p class="text-sm text-gray-500 mb-4">${place.formatted_address || "Endereço não disponível"}</p>
                    
                    <div class="park-info-line">
                        <i class="fa-solid fa-clock"></i> <span>Horário de Funcionamento: ${simulatedHours}</span>
                    </div>
                    <div class="park-info-line">
                        ${websiteLink}
                    </div>
                    <div class="park-info-line">
                        <i class="fa-solid fa-phone"></i> <span>Telefone: ${place.formatted_phone_number || "Não disponível"}</span>
                    </div>

                    <button class="create-review-button">Criar avaliação</button>

                    <h3 class="section-title">Participantes (${simulatedParticipants.length})</h3>
                    <div style="display: flex; margin-bottom: 20px;">
                        ${simulatedParticipants.map(p => `<img src="${p.photo}" class="participant-photo">`).join('')}
                    </div>

                    <h3 class="section-title">Avaliações sobre o Evento</h3>
                    <div class="review-card">
                        <img src="src/assets/avatar3.png" class="reviewer-photo"> <div>
                            <p class="font-bold text-sm">${simulatedReview.name}</p>
                            <div class="stars">${renderStars(simulatedReview.rating)}</div>
                            <p class="text-xs text-gray-600 mt-1">${simulatedReview.text}</p>
                        </div>
                    </div>
                </div>
            `;
            
            detailsContainer.innerHTML = content;
            detailsContainer.style.transition = 'transform 0.3s ease-out';
            detailsContainer.style.transform = 'translateY(0)';
            detailsContainer.style.display = 'block'; 
            
        } else {
            detailsContainer.innerHTML = `
                <div class="details-header">
                    <i class="fa-solid fa-arrow-left back-arrow" onclick="hideDetailsPane()"></i>
                    <h2>Detalhes do parque</h2>
                </div>
                <div class="park-content">
                    <b>${title}</b><br>Não foi possível carregar detalhes.
                </div>
            `;
            detailsContainer.style.transition = 'transform 0.3s ease-out'; 
            detailsContainer.style.transform = 'translateY(0)'; 
            detailsContainer.style.display = 'block'; 
        }
    });
}

function addMarkerUser(position, title) {
    const m = new google.maps.Marker({ 
        map, 
        position, 
        title
    });
    markers.push(m);
}

function clearMarkers() {
    markers.forEach(m => m.setMap(null));
    markers = [];
}

window.initMap = initMap;

// ----------------------------------------------------
// Código Existente para o Menu de Filtro (Atualizado com setupClickToClose)
// ----------------------------------------------------
document.addEventListener("DOMContentLoaded", function() {
    const filterButton = document.getElementById("filter-menu-button");
    const filterMenu = document.getElementById("filter-menu");

    // Alterna a visibilidade do menu quando o botão é clicado
    filterButton.addEventListener("click", () => {
        filterMenu.classList.toggle("filter-menu-visible");
        filterMenu.classList.toggle("filter-menu-hidden");
    });

    // Opcional: Oculta o menu se o usuário clicar fora dele
    document.addEventListener("click", (event) => {
        // Esta lógica de clique do menu de filtro DEVE vir ANTES da lógica de clique dos detalhes.
        // A variável wasDragged não afeta o filtro do menu.
        if (!filterMenu.contains(event.target) && !filterButton.contains(event.target)) {
            if (filterMenu.classList.contains("filter-menu-visible")) {
                filterMenu.classList.remove("filter-menu-visible");
                filterMenu.classList.add("filter-menu-hidden");
            }
        }
    });

    // Opcional: Lógica para o botão "Aplicar"
    const applyButton = document.getElementById("apply-filter-button");
    applyButton.addEventListener("click", () => {
        hideDetailsPane(); 
        
        const currentCenter = map.getCenter();
        buscarParquesProximos(currentCenter);

        filterMenu.classList.remove("filter-menu-visible");
        filterMenu.classList.add("filter-menu-hidden");
    });

    // Garante que o container de detalhes seja recuperado ao carregar o DOM
    detailsContainer = document.getElementById("park-details");
    setupDragEvents();
    setupClickToClose(); // Chama a função para fechar no clique
});

// No final do script.js
window.initMap = initMap;
