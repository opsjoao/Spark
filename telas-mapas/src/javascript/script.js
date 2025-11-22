// ====================================================
// VARIÁVEIS GLOBAIS E FUNÇÕES GLOBAIS DE EVENTO (TOPO DO ARQUIVO)
// ====================================================

// NOVOS DADOS PARA OS ELEMENTOS DO PARQUE
const PARK_AMENITIES = [
    { icon: "src/assets/icon_bebedouro.svg", label: "Bebedouro", type: 'amenity', color: '#20B2AA' },
{ icon: "src/assets/icon_banheiro.svg", label: "Banheiro", type: 'amenity', color: '#3F51B5' },
{ icon: "src/assets/icon_pista_skate.svg", label: "Pista de Skate", type: 'amenity', color: '#E141AC' },
{ icon: "src/assets/icon_quadra_volei.svg", label: "Quadra de Vôlei", type: 'amenity', color: '#8BC34A' },
{ icon: "src/assets/icon_quadra_futebol.svg", label: "Quadra de Futebol", type: 'amenity', color: '#212121' },
{ icon: "src/assets/icon_quadra_basquete.svg", label: "Quadra de Basquete", type: 'amenity', color: '#FF7043' },
{ icon: "src/assets/icon_bicicleta.svg", label: "Bicicleta", type: 'amenity', color: '#FFC107' },
{ icon: "src/assets/icon_pedalinho.svg", label: "Pedalinho", type: 'amenity', color: '#673AB7' },
{ icon: "src/assets/icon_museu.svg", label: "Museu", type: 'amenity', color: '#E1418E' },
{ icon: "src/assets/icon_quiosque.svg", label: "Quiosque", type: 'amenity', color: '#FFEB3B' },
{ icon: "src/assets/icon_lago.svg", label: "Lago", type: 'amenity', color: '#00BCD4' },
{ icon: "src/assets/icon_churrasqueira.svg", label: "Churrasqueira", type: 'amenity', color: '#D32F2F' },
// NOVO ITEM PARA ADICIONAR
{ icon: "src/assets/icon_adicionar.svg", label: "Adicionar sugestão", type: 'action', color: '#5ED925' }
];


let map, markers = [], autocomplete, placesService, infoWindow;

// VARIÁVEIS GLOBAIS NOVAS PARA O EVENTO
let currentParkLocation = "";
let currentParkName = "";

// Variáveis de controle de arrasto
let isDragging = false;
let startY = 0;
let initialTranslateY = 0;
let wasDragged = false;
const DRAG_THRESHOLD = 5;
let detailsContainer = document.getElementById("park-details");
const PLACEHOLDER_AVATAR_URL = "src/assets/avatar.png";

const SAVE_EVENT_URL = '/Spark-main/telas-mapas/src/javascript/salvar_evento_mapa.php';

// ====================================================
// FUNÇÕES GLOBAIS DE EVENTO
// ====================================================

window.closeCreateEventScreen = function() {
    const modal = document.getElementById('create-event-modal');
    if (modal) {
        modal.remove();
    }
}

window.closeModalAndStopPropagation = function(event) {
    if (event) {
        event.stopPropagation();
    }
    window.closeCreateEventScreen();
}

window.handleSaveEvent = function() {
    const eventTitle = document.getElementById('event-title').value;
    const eventDescription = document.getElementById('event-description').value;
    const eventLocation = document.getElementById('event-location').value;
    const eventDate = document.getElementById('event-date').value;
    const parkName = currentParkName;

    if (!eventTitle || !eventDate || !parkName || !eventDescription) {
        alert("Por favor, preencha o nome, a descrição, a data/hora do evento e certifique-se de que o parque foi carregado corretamente.");
        return;
    }

    const formData = new FormData();
    formData.append('event_title', eventTitle);
    formData.append('event_description', eventDescription);
    formData.append('event_date', eventDate);
    formData.append('event_location', eventLocation);
    formData.append('park_name', parkName);
    formData.append('park_cep', window.currentParkPostalCode || "");

    fetch(SAVE_EVENT_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log("Evento salvo com sucesso!");
            window.closeCreateEventScreen();
            window.location.href = '/Spark-main/tela-atividades/atividades.php?aba=meus-eventos';
        } else {
            alert("Erro ao salvar o evento: " + data.message);
        }
    })
    .catch(error => {
        console.error('Erro de rede ou servidor:', error);
        alert("Erro ao salvar o evento. Verifique sua conexão.");
        window.closeCreateEventScreen();
    });
}

function openCreateEventScreen() {
    if (!currentParkName || !currentParkLocation) {
        alert("Erro: Não foi possível obter o local do parque para criar o evento.");
        return;
    }

    const modal = document.createElement('div');
    modal.id = 'create-event-modal';

    modal.addEventListener('click', function(event) {
        if (event.target.id === 'create-event-modal') {
            window.closeCreateEventScreen();
        }
        event.stopPropagation();
    });

    modal.innerHTML = `
    <div onclick="event.stopPropagation()" style="background: white; padding: 20px; border-radius: 10px; width: 90%; max-width: 400px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);">
    <h2 style="font-size: 1.2em; margin-bottom: 15px;">Criar Novo Evento em ${currentParkName}</h2>
    <div style="margin-bottom: 10px;">
    <label for="event-title" style="display: block; font-weight: bold; margin-bottom: 5px;">Nome do Evento:</label>
    <input type="text" id="event-title" placeholder="Piquenique de Primavera" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
    <div style="margin-bottom: 10px;">
    <label for="event-description" style="display: block; font-weight: bold; margin-bottom: 5px;">Descrição:</label>
    <textarea id="event-description" placeholder="O que vocês vão fazer neste evento?" rows="3" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; resize: none;"></textarea>
    </div>
    <div style="margin-bottom: 10px;">
    <label for="event-location" style="display: block; font-weight: bold; margin-bottom: 5px;">Localização:</label>
    <input type="text" id="event-location" value="${currentParkLocation}" readonly style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; background-color: #eee;">
    </div>
    <div style="margin-bottom: 20px;">
    <label for="event-date" style="display: block; font-weight: bold; margin-bottom: 5px;">Data e Hora:</label>
    <input type="datetime-local" id="event-date" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    </div>
    <button id="save-event-button" style="width: 100%; padding: 10px; background-color: #5ED925; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; margin-bottom: 10px;">Salvar Evento</button>

    <button class="close-modal-button" onclick="closeModalAndStopPropagation(event)" style="width: 100%; padding: 10px; background-color: #ccc; color: #333; border: none; border-radius: 4px; cursor: pointer;">Fechar</button>
    </div>
    `;

    document.body.appendChild(modal);
    modal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 8000; /* AJUSTADO PARA GARANTIR SOBREPOSIÇÃO */
    `;

    const saveButton = document.getElementById('save-event-button');
    if (saveButton) {
        saveButton.addEventListener('click', window.handleSaveEvent);
    }
}

// ====================================================
// NOVA FUNÇÃO PARA ABRIR TELA DE SELEÇÃO COMPLETA
// ====================================================

/**
 * Abre o modal/tela completa para selecionar as amenidades.
 */
window.openAllAmenitiesSelectionScreen = function() { // <--- CORREÇÃO APLICADA AQUI
    if (!currentParkName) {
        alert("Erro: Parque não carregado.");
        return;
    }

    // Filtra apenas as amenidades (excluindo o botão de 'Adicionar sugestão')
    const amenitiesList = PARK_AMENITIES.filter(a => a.type === 'amenity');

    // Geração do HTML para a tela completa
    const allAmenitiesHtml = amenitiesList.map(item => `
    <div class="full-amenity-item" style="cursor: pointer;">
    <div class="full-amenity-icon-wrapper" style="background-color: white; border: 4px solid ${item.color};">
    <img src="${item.icon}" alt="${item.label}" class="full-amenity-icon" />
    </div>
    <p class="full-amenity-label">${item.label}</p>
    </div>
    `).join('');

    const modal = document.createElement('div');
    modal.id = 'full-amenities-modal';

    modal.addEventListener('click', function(event) {
        // Fecha o modal se o clique for no fundo (a própria div modal)
        if (event.target.id === 'full-amenities-modal') {
            modal.remove();
        }
        // Previne que o evento se propague para elementos de fundo (como o mapa)
        event.stopPropagation();
    }); // Fim do addEventListener corrigido.

    modal.innerHTML = `
    <div onclick="event.stopPropagation()" class="full-amenities-content-wrapper">
    <h2 class="full-amenities-header">Nesse parque tem</h2>

    <div class="full-amenities-grid">
    ${allAmenitiesHtml}
    </div>

    <button onclick="alert('Funcionalidade de Seleção salva (simulada)!'); document.getElementById('full-amenities-modal').remove();" class="full-amenities-save-button">
    Salvar Seleção
    </button>
    <button onclick="document.getElementById('full-amenities-modal').remove();" class="full-amenities-close-button">
    Fechar
    </button>
    </div>
    `;

    document.body.appendChild(modal);
    modal.style.cssText = `
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9); /* Fundo escuro */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999; /* Z-INDEX MÁXIMO PARA COBRIR TUDO */
    overflow-y: auto;
    `;
}

// ====================================================
// FIM DAS FUNÇÕES GLOBAIS DE EVENTO
// ====================================================

function initMap() {
    const fallbackLocal = { lat: -23.5505, lng: -46.6333 };

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
    const raio = document.getElementById("radiusInput").value * 1000;
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

function hideDetailsPane() {
    const detailsContainer = document.getElementById("park-details");

    if (detailsContainer && detailsContainer.style.display !== 'none') {
        detailsContainer.style.transition = 'transform 0.3s ease-in';
        detailsContainer.style.transform = 'translateY(100%)';

        setTimeout(() => {
            detailsContainer.style.display = 'none';
            detailsContainer.style.transform = 'translateY(0)';
            detailsContainer.style.transition = 'none';
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

function setupClickToClose() {
    document.addEventListener('click', (event) => {
        if (wasDragged) {
            wasDragged = false;
            return;
        }

        const isClickInsidePane = detailsContainer.contains(event.target);
        const isClickOnMarker = event.target.tagName === 'IMG' && event.target.src.includes('pin_verde.png') && detailsContainer.style.display === 'none';
        const isClickOnExpandButton = event.target.classList.contains('expand-hours-button') || event.target.closest('.expand-hours-button');
        // Novo: Verifica se o clique foi em algum dos modais, que devem ser ignorados
        const isClickOnModal = document.getElementById('full-amenities-modal') || document.getElementById('create-event-modal');

        if (detailsContainer.style.display === 'block' && !isClickInsidePane && !isClickOnMarker && !isClickOnExpandButton && !isClickOnModal) {
            hideDetailsPane();
        }
    });
}

function getTodayAndAllHours(weekdayText) {
    if (!weekdayText || weekdayText.length === 0) {
        return { todayHours: "Horário não disponível", allHoursHtml: "Nenhum horário disponível." };
    }

    const todayIndex = new Date().getDay();
    const weekDays = ["Domingo", "Segunda-feira", "Terça-feira", "Quarta-feira", "Quinta-feira", "Sexta-feira", "Sábado"];
    const todayName = weekDays[todayIndex];

    let todayHours = "Não disponível";
    let foundToday = false;

    const allHoursHtml = weekdayText.map(line => {
        if (line.startsWith(todayName)) {
            todayHours = line.replace(`${todayName}: `, '').trim();
            foundToday = true;
            return `<p class="current-day">${line}</p>`;
        }
        return `<p>${line}</p>`;
    }).join('');

    if (!foundToday && weekdayText.length > 0) {
        todayHours = weekdayText[0].split(': ').pop() || weekdayText[0];
    }

    return { todayHours, allHoursHtml };
}

// Função para simular quais amenidades o parque TEM
function getParkAmenities() {
    return PARK_AMENITIES;
}


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

            currentParkLocation = place.formatted_address || "Endereço não disponível";
            currentParkName = place.name || title;

            // Extrair CEP
            let parkPostalCode = "";
            if (place.address_components) {
                parkPostalCode = getPostalCode(place.address_components);
            }
            window.currentParkPostalCode = parkPostalCode; // salvar CEP globalmente


            const hoursData = (place.opening_hours && place.opening_hours.weekday_text)
            ? getTodayAndAllHours(place.opening_hours.weekday_text)
            : { todayHours: "Horário não disponível", allHoursHtml: "Nenhum horário disponível." };

            const realTodayHours = hoursData.todayHours;
            const realAllHoursHtml = hoursData.allHoursHtml;

            const realAbout = place.editorial_summary ?
            place.editorial_summary.overview :
            "Informações detalhadas sobre este parque não estão disponíveis. No entanto, é um ótimo lugar para desfrutar da natureza e de atividades ao ar livre!";

            const parkDescription = realAbout;

            const simulatedParticipants = [
                { photo: "src/assets/avatar1.png" },
                { photo: "src/assets/avatar2.png" }
            ];
            const simulatedReview = [
                {
                    name: "Robert Renan",
                    reviewPhoto: "src/assets/avatar3.png",
                    text: "O piquenique foi espetacular, as pessoas eram muito divertidas, e a comida era muito boa! Se tiver mais vozes, participem, pois vale muito a pena!",
                    rating: 5
                }
            ];

            const review = simulatedReview.length > 0 ? simulatedReview[0] : {};

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

            const participantsHtml = simulatedParticipants.map(p => `
            <img src="${p.photo || ''}"
            class="participant-photo"
            onerror="this.onerror=null; this.src='${PLACEHOLDER_AVATAR_URL}';"
            alt="Foto do Participante"
            >
            `).join('');

            // --- CÓDIGO DO CARROSSEL DE AMENIDADES MODIFICADO (Borda de 4px) ---
            const amenities = getParkAmenities();
            const amenitiesCarouselHtml = amenities.map(item => {

                let onclickEvent = '';
                let cursorStyle = '';

                // Usamos a nova propriedade 'color' ou um fallback
                const amenityColor = item.color || '#ddd';

                if (item.type === 'action') {
                    onclickEvent = `onclick="openAllAmenitiesSelectionScreen()"`;
                    cursorStyle = 'cursor: pointer;';
                } else {
                    cursorStyle = '';
                }

                // Aplica background branco e borda de 4px com a cor do item (AGORA 4PX)
                const wrapperStyle = `background-color: white; border: 4px solid ${amenityColor};`;

                return `
                <div class="amenity-item" ${onclickEvent} style="${cursorStyle}">
                <div class="amenity-icon-wrapper" style="${wrapperStyle}">
                <img src="${item.icon}" alt="${item.label}" class="amenity-icon" />
                </div>
                <p class="amenity-label">${item.label}</p>
                </div>
                `;
            }).join('');
            // --- FIM CÓDIGO DO CARROSSEL DE AMENIDADES MODIFICADO ---

            const content = `
            <div class="details-header" style="text-align: center;">
            <h2>Detalhes do parque</h2>
            </div>

            <div class="park-image-wrapper">
            ${photoUrl ? `<img src="${photoUrl}" alt="${place.name}" class="park-image-cover">` : ''}

            <button id="create-event-button-${placeId}" class="create-event-button">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" ><path fill="rgba(255, 255, 255, 1)" d="M352 128C352 110.3 337.7 96 320 96C302.3 96 288 110.3 288 128L288 288L128 288C110.3 288 96 302.3 96 320C96 337.7 110.3 352 128 352L288 352L288 512C288 529.7 302.3 544 320 544C337.7 544 352 529.7 352 512L352 352L512 352C529.7 352 544 337.7 544 320C544 302.3 529.7 288 512 288L352 288L352 128z"/></svg>
            </button>
            </div>
            <div class="park-content">
            <h1 class="text-xl font-bold text-gray-800 mb-2">${place.name || title}</h1>
            <p class="text-sm text-gray-500 mb-4">${currentParkLocation}</p>

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

            <h3 class="section-title">Nesse parque tem</h3>
            <div class="amenities-carousel-container">
            <div class="amenities-carousel-track">
            ${amenitiesCarouselHtml}
            </div>
            </div>
            <hr class="section-divider">
            <h3 class="section-title">${simulatedReview.length} Avaliações</h3>
            <div class="review-card">
            <img src="${review.reviewPhoto || ''}"
            class="reviewer-photo"
            onerror="this.onerror=null; this.src='${PLACEHOLDER_AVATAR_URL}';"
            alt="Foto do Avaliador">
            <div>
            <p class="font-bold text-sm">${review.name}</p>
            <div class="stars">${renderStars(review.rating)}</div>
            <p class="text-xs text-gray-600 mt-1">${review.text}</p>
            </div>
            </div>
            </div>
            `;

            detailsContainer.innerHTML = content;
            detailsContainer.style.transition = 'transform 0.3s ease-out';
            detailsContainer.style.display = 'block';
            detailsContainer.style.transform = 'translateY(0)';

            const eventButton = document.getElementById(`create-event-button-${placeId}`);
            if (eventButton) {
                eventButton.addEventListener('click', openCreateEventScreen);
            }

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

function getPostalCode(components) {
    for (const comp of components) {
        if (comp.types.includes("postal_code")) {
            return comp.long_name;
        }
    }
    return "";
}


document.addEventListener("DOMContentLoaded", function() {
    const filterButton = document.getElementById("filter-menu-button");
    const filterMenu = document.getElementById("filter-menu");

    filterButton.addEventListener("click", () => {
        filterMenu.classList.toggle("filter-menu-visible");
        filterMenu.classList.toggle("filter-menu-hidden");
    });

    document.addEventListener("click", (event) => {
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
