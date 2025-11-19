let map, markers = [], autocomplete, placesService, infoWindow;

// Variáveis de controle de arrasto
let isDragging = false;
let startY = 0;
let initialTranslateY = 0;
let wasDragged = false; 
const DRAG_THRESHOLD = 5; 
let detailsContainer = document.getElementById("park-details"); 
// ----------------------------------------------------
// VARIÁVEL DO PLACEHOLDER
// ----------------------------------------------------
const PLACEHOLDER_AVATAR_URL = "src/assets/avatar.png"; 


function initMap() {
    const fallbackLocal = { lat: -23.5505, lng: -46.6333 }; // SP

    map = new google.maps.Map(document.getElementById("map"), {
        center: fallbackLocal,
        zoom: 13
    });

    detailsContainer = document.getElementById("park-details");
    setupDragEvents();
    setupClickToClose(); 

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
    const detailsContainer = document.getElementById("park-details");
    
    // Esconde o painel e reseta o transform para a próxima vez
    if (detailsContainer && detailsContainer.style.display !== 'none') {
        // Usa transform para animar para fora da tela (desliza para baixo)
        detailsContainer.style.transition = 'transform 0.3s ease-in';
        detailsContainer.style.transform = 'translateY(100%)'; 

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
    
    detailsContainer.addEventListener('mousedown', startDrag);
    detailsContainer.addEventListener('touchstart', startDrag);
    
    document.addEventListener('mousemove', drag);
    document.addEventListener('touchmove', drag);
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchend', endDrag);
}

function startDrag(event) {
    const initialY = event.type.startsWith('touch') ? event.touches[0].clientY : event.clientY;

    if (detailsContainer.scrollHeight > detailsContainer.clientHeight && detailsContainer.scrollTop > 0) {
        return; 
    }
    
    isDragging = true;
    wasDragged = false; 
    startY = initialY;
    
    detailsContainer.style.transition = 'none'; 
    detailsContainer.style.cursor = 'grabbing';
    
    const style = window.getComputedStyle(detailsContainer);
    const transform = style.getPropertyValue('transform');
    const match = transform.match(/matrix\(1, 0, 0, 1, 0, (.*)\)/);
    initialTranslateY = match ? parseFloat(match[1]) : 0;
}

function drag(event) {
    if (!isDragging) {
        return; 
    }

    const currentY = event.type.startsWith('touch') ? event.touches[0].clientY : event.clientY;
    let diffY = currentY - startY;
    
    if (detailsContainer.scrollTop > 0 && diffY < 0) {
        isDragging = false; 
        return; 
    }

    event.preventDefault();
    
    if (Math.abs(currentY - startY) > DRAG_THRESHOLD) {
        wasDragged = true;
    }

    if (diffY < 0) {
        diffY = 0;
    }

    detailsContainer.style.transform = `translateY(${initialTranslateY + diffY}px)`;
}

function endDrag(event) {
    if (!isDragging) return;
    
    isDragging = false;
    detailsContainer.style.transition = 'transform 0.3s ease-out';
    detailsContainer.style.cursor = 'grab';

    const style = window.getComputedStyle(detailsContainer);
    const transform = style.getPropertyValue('transform');
    const match = transform.match(/matrix\(1, 0, 0, 1, 0, (.*)\)/);
    const finalTranslateY = match ? parseFloat(match[1]) : 0;
    
    const threshold = detailsContainer.offsetHeight * 0.25; 

    if (finalTranslateY > threshold) {
        hideDetailsPane();
    } else {
        detailsContainer.style.transform = 'translateY(0)';
    }
}

// ----------------------------------------------------
// Função para fechar no clique fora (MODIFICADA)
// ----------------------------------------------------
function setupClickToClose() {
    document.addEventListener('click', (event) => {
        if (wasDragged) {
            wasDragged = false;
            return;
        }

        const isClickInsidePane = detailsContainer.contains(event.target);
        // Ajuste no check do marker para ser mais robusto, verificando se o display está ativo
        const isClickOnMarker = event.target.tagName === 'IMG' && event.target.src.includes('pin_verde.png') && detailsContainer.style.display === 'none';

        // NOVO CHECK: Se o clique foi no botão de expansão do horário, não feche o painel.
        const isClickOnExpandButton = event.target.classList.contains('expand-hours-button') || event.target.closest('.expand-hours-button');


        if (detailsContainer.style.display === 'block' && !isClickInsidePane && !isClickOnMarker && !isClickOnExpandButton) {
            hideDetailsPane();
        }
    });
}

// ----------------------------------------------------
// NOVA FUNÇÃO: Obtém o horário de hoje e o HTML completo
// ----------------------------------------------------
function getTodayAndAllHours(weekdayText) {
    if (!weekdayText || weekdayText.length === 0) {
        return { todayHours: "Horário não disponível", allHoursHtml: "Nenhum horário disponível." };
    }

    // Mapeamento dos dias para encontrar a correspondência exata. 
    // O getDay() retorna 0 (Dom) a 6 (Sáb). A API do Google retorna os dias em Português.
    const todayIndex = new Date().getDay(); 
    const weekDays = ["Domingo", "Segunda-feira", "Terça-feira", "Quarta-feira", "Quinta-feira", "Sexta-feira", "Sábado"];
    const todayName = weekDays[todayIndex];
    
    let todayHours = "Não disponível";
    let foundToday = false;

    // 1. Encontrar o horário de hoje
    const allHoursHtml = weekdayText.map(line => {
        // Usa `startsWith` para encontrar o dia correto
        if (line.startsWith(todayName)) {
            // Remove o nome do dia e os dois pontos/espaço para deixar só o horário
            todayHours = line.replace(`${todayName}: `, '').trim();
            foundToday = true;
            return `<p class="current-day">${line}</p>`; // Adiciona classe para destaque no CSS
        }
        return `<p>${line}</p>`;
    }).join('');

    // 2. Se não encontrou o dia de hoje (pode ser problema de fuso/formato), usa o primeiro como fallback
    if (!foundToday && weekdayText.length > 0) {
        todayHours = weekdayText[0].split(': ').pop() || weekdayText[0]; // Tenta isolar o horário do primeiro item
    }

    return { todayHours, allHoursHtml };
}


// ----------------------------------------------------
// Função showDetailsPane (MODIFICADA)
// ----------------------------------------------------
function showDetailsPane(placeId, title) {
    const request = {
        placeId: placeId,
        fields: ['name', 'formatted_address', 'website', 'formatted_phone_number', 'photos', 'opening_hours', 'rating', 'user_ratings_total', 'reviews', 'editorial_summary']
    };

    placesService.getDetails(request, (place, status) => {
        detailsContainer = document.getElementById("park-details"); 
        
        if (status === google.maps.places.PlacesServiceStatus.OK) {
            let photoUrl = "";
            if (place.photos && place.photos.length > 0) {
                photoUrl = place.photos[0].getUrl({ maxWidth: detailsContainer.clientWidth || 400 }); 
            }

            // --- Lógica do Horário Atualizada ---
            const hoursData = (place.opening_hours && place.opening_hours.weekday_text) 
                ? getTodayAndAllHours(place.opening_hours.weekday_text)
                : { todayHours: "Horário não disponível", allHoursHtml: "Nenhum horário disponível." };

            const realTodayHours = hoursData.todayHours;
            const realAllHoursHtml = hoursData.allHoursHtml;
            // ------------------------------------

            const realAbout = place.editorial_summary ? 
                place.editorial_summary.overview : 
                "Informações detalhadas sobre este parque não estão disponíveis. No entanto, é um ótimo lugar para desfrutar da natureza e de atividades ao ar livre!";
            
            const parkDescription = realAbout;

            const simulatedParticipants = [
                { photo: "src/assets/avatar1.png" }, // Foto simulada 1
                { photo: "src/assets/avatar2.png" }  // Foto simulada 2
            ];
            const simulatedReview = {
                name: "Robert Renan",
                reviewPhoto: "src/assets/avatar3.png", 
                text: "O piquenique foi espetacular, as pessoas eram muito divertidas, e a comida era muito boa! Se tiver mais vozes, participem, pois vale muito a pena!",
                rating: 5
            };
            
            const websiteLink = place.website ? 
                `<i class="fa-solid fa-globe"></i> <a href="${place.website}" target="_blank" style="color:#7CBD64;">Site: ${new URL(place.website).hostname}</a>` :
                `<i class="fa-solid fa-globe"></i> <span>Site: Não disponível</span>`;

            const renderStars = (rating) => {
                let stars = '';
                for (let i = 1; i <= 5; i++) {
                    stars += `<i class="fa-solid fa-star" style="color: ${i <= rating ? 'gold' : '#ccc'};"></i>`;
                }
                return stars;
            };

            // 1. Placeholder nos Participantes
            const participantsHtml = simulatedParticipants.map(p => `
                <img src="${p.photo || ''}" 
                    class="participant-photo" 
                    onerror="this.onerror=null; this.src='${PLACEHOLDER_AVATAR_URL}';"
                    alt="Foto do Participante"
                >
            `).join('');

            const content = `
                <div class="details-header" style="text-align: center;">
                    <h2>Detalhes do parque</h2>
                </div>

                <div class="park-image-container">
                    ${photoUrl ? `<img src="${photoUrl}" alt="${place.name}" class="park-image-cover">` : ''}       

                    <button class="create-event-button" onclick="alert('Funcionalidade de Criar Evento em desenvolvimento!');">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" ><path fill="rgba(255, 255, 255, 1)" d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z"/></svg>
                   </button>
                </div>
                <div class="park-content">
                    <h1 class="text-xl font-bold text-gray-800 mb-2">${place.name || title}</h1>
                    <p class="text-sm text-gray-500 mb-4">${place.formatted_address || "Endereço não disponível"}</p>
                    
                    <div class="park-info-line">
                        <i class="fa-solid fa-clock"></i> 
                        <span>Horário de Hoje:</span>
                        <span class="today-hours-display">${realTodayHours}</span>
                        
                        <button class="expand-hours-button" onclick="document.getElementById('full-hours').classList.toggle('hidden'); this.querySelector('i').classList.toggle('rotate-180');" aria-expanded="false">
                            Ver todos os dias <i class="fa-solid fa-chevron-down"></i>
                        </button>
                    </div>

                    <div id="full-hours" class="full-hours-dropdown hidden">
                        ${realAllHoursHtml}
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
                        ${participantsHtml} 
                    </div>

                    <h3 class="section-title">Avaliações sobre o Evento</h3>
                    <div class="review-card">
                        <img src="${simulatedReview.reviewPhoto || ''}" 
                            class="reviewer-photo"
                            onerror="this.onerror=null; this.src='${PLACEHOLDER_AVATAR_URL}';" 
                            alt="Foto do Avaliador"> 
                        <div>
                            <p class="font-bold text-sm">${simulatedReview.name}</p>
                            <div class="stars">${renderStars(simulatedReview.rating)}</div>
                            <p class="text-xs text-gray-600 mt-1">${simulatedReview.text}</p>
                        </div>
                    </div>
                </div>
            `;
            
            detailsContainer.innerHTML = content;
            detailsContainer.style.transition = 'transform 0.3s ease-out';
            detailsContainer.style.display = 'block';
            detailsContainer.style.transform = 'translateY(0)';
            
        } else {
            detailsContainer.innerHTML = `
                <div class="details-header" style="text-align: center;">
                    <h2>Detalhes do parque</h2>
                </div>
                <div class="park-content">
                    <h1 class="text-xl font-bold text-gray-800 mb-2">${title}</h1>
                    <p>Não foi possível carregar detalhes.</p>
                </div>
            `;
            detailsContainer.style.transition = 'transform 0.3s ease-out'; 
            detailsContainer.style.display = 'block';
            detailsContainer.style.transform = 'translateY(0)'; 
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
// Código Existente para o Menu de Filtro (Mantido)
// ----------------------------------------------------
document.addEventListener("DOMContentLoaded", function() {
    const filterButton = document.getElementById("filter-menu-button");
    const filterMenu = document.getElementById("filter-menu");

    filterButton.addEventListener("click", () => {
        filterMenu.classList.toggle("filter-menu-visible");
        filterMenu.classList.toggle("filter-menu-hidden");
    });

    document.addEventListener("click", (event) => {
        // NOVO CHECK: Se o clique foi no botão de expandir/colapsar do horário, não feche o menu de filtro.
        const isClickOnExpandButton = event.target.classList.contains('expand-hours-button') || event.target.closest('.expand-hours-button');
        if (isClickOnExpandButton) return;

        if (!filterMenu.contains(event.target) && !filterButton.contains(event.target)) {
            if (filterMenu.classList.contains("filter-menu-visible")) {
                filterMenu.classList.remove("filter-menu-visible");
                filterMenu.classList.add("filter-menu-hidden");
            }
        }
    });

    const applyButton = document.getElementById("apply-filter-button");
    applyButton.addEventListener("click", () => {
        hideDetailsPane(); 
        
        const currentCenter = map.getCenter();
        buscarParquesProximos(currentCenter);

        filterMenu.classList.remove("filter-menu-visible");
        filterMenu.classList.add("filter-menu-hidden");
    });

    detailsContainer = document.getElementById("park-details");
    setupDragEvents();
    setupClickToClose();
});

window.initMap = initMap;
