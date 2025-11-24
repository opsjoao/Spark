// ====================================================
// VARIÁVEIS GLOBAIS E FUNÇÕES GLOBAIS DE EVENTO (TOPO DO ARQUIVO)
// ====================================================

// NOVOS DADOS PARA OS ELEMENTOS DO PARQUE
const PARK_AMENITIES_DATA = [
    { icon: "src/assets/icon_bebedouro.svg", label: "Bebedouro", type: 'amenity', color: '#41E189', isSelected: false },
    { icon: "src/assets/icon_banheiro.svg", label: "Banheiro", type: 'amenity', color: '#4169E1', isSelected: false },
    { icon: "src/assets/icon_pista_skate.svg", label: "Pista de Skate", type: 'amenity', color: '#E141AC', isSelected: false },
    { icon: "src/assets/icon_quadra_volei.svg", label: "Quadra de Vôlei", type: 'amenity', color: '#8CE141', isSelected: false },
    { icon: "src/assets/icon_quadra_futebol.svg", label: "Quadra de Futebol", type: 'amenity', color: '#212121', isSelected: false },
    { icon: "src/assets/icon_quadra_basquete.svg", label: "Quadra de Basquete", type: 'amenity', color: '#FF7043', isSelected: false },
    { icon: "src/assets/icon_bicicleta.svg", label: "Bicicleta", type: 'amenity', color: '#FFC107', isSelected: false },
    { icon: "src/assets/icon_pedalinho.svg", label: "Pedalinho", type: 'amenity', color: '#673AB7', isSelected: false },
    { icon: "src/assets/icon_museu.svg", label: "Museu", type: 'amenity', color: '#E1418E', isSelected: false },
    { icon: "src/assets/icon_quiosque.svg", label: "Quiosque", type: 'amenity', color: '#FFEB3B', isSelected: false },
    { icon: "src/assets/icon_lago.svg", label: "Lago", type: 'amenity', color: '#41E1D9', isSelected: false },
    { icon: "src/assets/icon_churrasqueira.svg", label: "Churrasqueira", type: 'amenity', color: '#D32F2F', isSelected: false },
    // NOVO ITEM PARA ADICIONAR
    { icon: "src/assets/icon_adicionar.svg", label: "Adicionar sugestão", type: 'action', color: '#5ED925' }
];

let parkAmenitiesState = JSON.parse(JSON.stringify(PARK_AMENITIES_DATA));

let map, markers = [], autocomplete, placesService, infoWindow;

// VARIÁVEIS GLOBAIS NOVAS PARA O EVENTO
let currentParkLocation = "";
let currentParkName = "";

window.currentParkPlaceId = ""; // NOVO: Armazena o ID único do Google Maps (definido como window global)
const PARK_AMENITIES_URL = '/Spark-main/telas-mapas/src/javascript/park_amenities.php'; // NOVO ENDPOINT
let parkAmenityCounts = {}; // NOVO: Armazenará as contagens de votos vindas do backend

// Variáveis de controle de arrasto
let isDragging = false;
let startY = 0;
let initialTranslateY = 0;
let wasDragged = false;
const DRAG_THRESHOLD = 5;
let detailsContainer = document.getElementById("park-details");
const PLACEHOLDER_AVATAR_URL = "src/assets/avatar.png";

const SAVE_EVENT_URL = '/Spark-main/telas-mapas/src/javascript/salvar_evento_mapa.php';
const PARK_REVIEWS_URL = '/Spark-main/telas-mapas/src/javascript/park_reviews.php'; // NOVO ENDPOINT

// ====================================================
// FUNÇÕES GLOBAIS DE EVENTO E AVALIAÇÕES
// ====================================================

/**
 * Abre o modal para adicionar uma avaliação do parque
 */
window.openAddReviewModal = function() {
    if (!window.currentParkPlaceId || !currentParkName) {
        alert("Erro: Parque não carregado.");
        return;
    }

    const modal = document.createElement('div');
    modal.id = 'add-review-modal';

    modal.addEventListener('click', function(event) {
        if (event.target.id === 'add-review-modal') {
            modal.remove();
        }
        event.stopPropagation();
    });

    modal.innerHTML = `
    <div onclick="event.stopPropagation()" style="background: white; padding: 25px; border-radius: 12px; width: 90%; max-width: 450px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);">
        <h2 style="font-size: 1.3em; margin-bottom: 20px; text-align: center; color: #333;">Avaliar ${currentParkName}</h2>
        
        <div style="text-align: center; margin-bottom: 20px;">
            <p style="margin-bottom: 10px; font-weight: bold; color: #555;">Sua avaliação:</p>
            <div id="review-stars" style="font-size: 2em; cursor: pointer;">
                ${[1, 2, 3, 4, 5].map(n => `<i class="fa-star" data-rating="${n}" style="color: #ddd; margin: 0 3px;"></i>`).join('')}
            </div>
            <input type="hidden" id="review-rating" value="0">
        </div>

        <div style="margin-bottom: 20px;">
            <label for="review-text" style="display: block; font-weight: bold; margin-bottom: 8px; color: #555;">Conte sua experiência:</label>
            <textarea id="review-text" placeholder="O que você achou do parque?" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; resize: vertical; font-family: inherit;"></textarea>
        </div>

        <button id="submit-review-button" style="width: 100%; padding: 12px; background-color: #5ED925; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; margin-bottom: 10px; font-size: 1em;">
            Enviar Avaliação
        </button>

        <button onclick="document.getElementById('add-review-modal').remove();" style="width: 100%; padding: 12px; background-color: #ccc; color: #333; border: none; border-radius: 6px; cursor: pointer; font-size: 1em;">
            Cancelar
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
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 8500;
    `;

    // Sistema de estrelas interativo
    const starsContainer = document.getElementById('review-stars');
    const stars = starsContainer.querySelectorAll('.fa-star');
    const ratingInput = document.getElementById('review-rating');

    stars.forEach(star => {
        star.classList.add('fa-regular'); // Estrela vazia por padrão

        // Hover
        star.addEventListener('mouseenter', () => {
            const rating = parseInt(star.dataset.rating);
            stars.forEach((s, idx) => {
                if (idx < rating) {
                    s.classList.remove('fa-regular');
                    s.classList.add('fa-solid');
                    s.style.color = 'gold';
                } else {
                    s.classList.remove('fa-solid');
                    s.classList.add('fa-regular');
                    s.style.color = '#ddd';
                }
            });
        });

        // Click
        star.addEventListener('click', () => {
            const rating = parseInt(star.dataset.rating);
            ratingInput.value = rating;
            
            // Fixa as estrelas
            stars.forEach((s, idx) => {
                if (idx < rating) {
                    s.classList.remove('fa-regular');
                    s.classList.add('fa-solid');
                    s.style.color = 'gold';
                } else {
                    s.classList.remove('fa-solid');
                    s.classList.add('fa-regular');
                    s.style.color = '#ddd';
                }
            });
        });
    });

    // Reset ao sair do mouse
    starsContainer.addEventListener('mouseleave', () => {
        const currentRating = parseInt(ratingInput.value);
        stars.forEach((s, idx) => {
            if (idx < currentRating) {
                s.classList.remove('fa-regular');
                s.classList.add('fa-solid');
                s.style.color = 'gold';
            } else {
                s.classList.remove('fa-solid');
                s.classList.add('fa-regular');
                s.style.color = '#ddd';
            }
        });
    });

    // Enviar avaliação
    document.getElementById('submit-review-button').addEventListener('click', window.handleSubmitReview);
}

/**
 * Envia a avaliação para o backend
 */
window.handleSubmitReview = function() {
    const rating = parseInt(document.getElementById('review-rating').value);
    const reviewText = document.getElementById('review-text').value.trim();
    const placeId = window.currentParkPlaceId;

    if (rating === 0) {
        alert("Por favor, selecione uma classificação de estrelas.");
        return;
    }

    if (reviewText.length < 10) {
        alert("Por favor, escreva uma avaliação com pelo menos 10 caracteres.");
        return;
    }

    const formData = new FormData();
    formData.append('place_id', placeId);
    formData.append('rating', rating);
    formData.append('review_text', reviewText);
    formData.append('park_name', currentParkName);
    formData.append('park_address', currentParkLocation);
    formData.append('park_cep', window.currentParkPostalCode || '');

    fetch(PARK_REVIEWS_URL, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Avaliação enviada com sucesso!");
            document.getElementById('add-review-modal').remove();
            
            // Recarrega o painel para mostrar a nova avaliação
            showDetailsPane(placeId, currentParkName);
        } else {
            alert("Erro ao enviar avaliação: " + (data.message || "Erro desconhecido."));
        }
    })
    .catch(error => {
        console.error('Erro ao enviar avaliação:', error);
        alert("Erro ao enviar avaliação. Verifique sua conexão.");
    });
}

// ====================================================
// FIM DAS FUNÇÕES GLOBAIS DE EVENTO E AVALIAÇÕES
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

/**
 * Alterna o estado de seleção de uma amenidade e redesenha a tela.
 * @param {string} label O rótulo da amenidade a ser alternada.
 */
window.toggleAmenitySelection = function(label) {
    const index = parkAmenitiesState.findIndex(a => a.label === label);
    if (index !== -1) {
        // Alterna o estado de seleção
        parkAmenitiesState[index].isSelected = !parkAmenitiesState[index].isSelected;
        // Redesenha a tela para refletir a mudança
        const modalElement = document.getElementById('full-amenities-modal');
        if (modalElement) {
            window.openAllAmenitiesSelectionScreen(true); // Redesenha
        }
    }
}


// ====================================================
// NOVA FUNÇÃO PARA ABRIR TELA DE SELEÇÃO COMPLETA
// ====================================================

/**
 * Abre o modal/tela completa para selecionar as amenidades.
 * @param {boolean} isRedrawing Indica se a função está sendo chamada para redesenhar o modal existente.
 */
window.openAllAmenitiesSelectionScreen = function(isRedrawing = false) {
    if (!currentParkName) {
        alert("Erro: Parque não carregado.");
        return;
    }

    const modalId = 'full-amenities-modal';
    let modal = document.getElementById(modalId);

    // Se estiver redesenhando, garante que o modal exista, caso contrário, aborta
    if (isRedrawing && !modal) {
        return;
    }

    // Filtra apenas as amenidades (excluindo o botão de 'Adicionar sugestão')
    const amenitiesList = parkAmenitiesState.filter(a => a.type === 'amenity');

    // Geração do HTML para a tela completa
    const allAmenitiesHtml = amenitiesList.map(item => {
        // ESTILO DE ATIVO/INATIVO (NOVO REQUISITO)
        const isSelected = item.isSelected;
        const backgroundColor = isSelected ? item.color : 'white';
        const borderColor = item.color; // Borda sempre com a cor do item
        // Ícone BRANCO quando ATIVO
        const iconColorStyle = isSelected ? 'filter: brightness(0) invert(1);' : '';

        // Adiciona a função de toggle ao clique
        const onclickAction = `window.toggleAmenitySelection('${item.label}')`;

        return `
        <div class="full-amenity-item" onclick="${onclickAction}" style="cursor: pointer;">
        <div class="full-amenity-icon-wrapper" style="background-color: ${backgroundColor}; border: 4px solid ${borderColor};">
        <img src="${item.icon}" alt="${item.label}" class="full-amenity-icon" style="${iconColorStyle}" />
        </div>
        <p class="full-amenity-label">${item.label}</p>
        </div>
        `;
    }).join('');

    // Se não estiver redesenhando, cria o modal do zero
    if (!isRedrawing) {
        modal = document.createElement('div');
        modal.id = modalId;

        modal.addEventListener('click', function(event) {
            // Fecha o modal se o clique for no fundo (a própria div modal)
            if (event.target.id === modalId) {
                modal.remove();
            }
            event.stopPropagation();
        });
        document.body.appendChild(modal);

        modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        overflow-y: auto;
        `;
    }

    // Injeta o novo HTML na modal (ou a nova modal, ou redesenha a existente)
    modal.innerHTML = `
    <div onclick="event.stopPropagation()" class="full-amenities-content-wrapper">
    <h2 class="full-amenities-header">Nesse parque tem</h2>

    <div class="full-amenities-grid">
    ${allAmenitiesHtml}
    </div>

    <button onclick="window.handleSaveAmenities();" class="full-amenities-save-button">
    Salvar Seleção
    </button>
    <button onclick="document.getElementById('full-amenities-modal').remove();" class="full-amenities-close-button">
    Fechar
    </button>
    </div>
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
    return parkAmenitiesState;
}


/**
 * Exibe o painel de detalhes do parque, buscando as informações do Google Places
 * e as contagens de amenidades do seu backend.
 * * @param {string} placeId O ID único do local (Place ID)
 * @param {string} title O nome do parque (fallback)
 */
function showDetailsPane(placeId, title) {
    const request = {
        placeId: placeId,
        fields: ['name', 'formatted_address', 'website', 'formatted_phone_number', 'photos', 'opening_hours', 'rating', 'user_ratings_total', 'reviews', 'editorial_summary', 'address_components']
    };

    placesService.getDetails(request, (place, status) => {
        detailsContainer = document.getElementById("park-details");

        if (status === google.maps.places.PlacesServiceStatus.OK) {
            let photoUrl = "";
            if (place.photos && place.photos.length > 0) {
                photoUrl = place.photos[0].getUrl({ maxWidth: detailsContainer.clientWidth || 400 });
            }

            // --- NOVO: SALVA VARIÁVEIS GLOBAIS IMPORTANTES ---
            currentParkLocation = place.formatted_address || "Endereço não disponível";
            currentParkName = place.name || title;
            window.currentParkPlaceId = placeId; // <-- SALVANDO O PLACE ID
            
            // Restaura o estado de seleção do modal para o padrão (limpa votos anteriores)
            parkAmenitiesState = JSON.parse(JSON.stringify(PARK_AMENITIES_DATA));

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
            
            // --- INICIA A BUSCA ASSÍNCRONA DE AMENIDADES ---
            // A renderização do painel de detalhes agora só ocorre DEPOIS que a contagem é carregada
            Promise.all([
                fetchAmenityCounts(placeId),
                fetchParkReviews(placeId)
            ]).then(([counts, reviews]) => {

                const realAbout = place.editorial_summary ?
                    place.editorial_summary.overview :
                    "Informações detalhadas sobre este parque não estão disponíveis. No entanto, é um ótimo lugar para desfrutar da natureza e de atividades ao ar livre!";

                const parkDescription = realAbout;

                // --- AVALIAÇÕES REAIS DO BANCO DE DADOS ---
                const reviewsCount = reviews.length;
                const reviewsHtml = reviews.length > 0 ? reviews.map(review => {
                    const renderStars = (rating) => {
                        let stars = '';
                        for (let i = 1; i <= 5; i++) {
                            stars += `<i class="fa-solid fa-star" style="color: ${i <= rating ? 'gold' : '#ccc'};"></i>`;
                        }
                        return stars;
                    };

                    return `
                    <div class="review-card">
                        <img src="${review.user_photo || PLACEHOLDER_AVATAR_URL}"
                            class="reviewer-photo"
                            onerror="this.onerror=null; this.src='${PLACEHOLDER_AVATAR_URL}';"
                            alt="Foto do Avaliador">
                        <div>
                            <p class="font-bold text-sm">${review.user_name || 'Usuário'}</p>
                            <div class="stars">${renderStars(review.rating)}</div>
                            <p class="text-xs text-gray-600 mt-1">${review.review_text}</p>
                            <p class="text-xs text-gray-400 mt-1">${review.created_at || ''}</p>
                        </div>
                    </div>
                    `;
                }).join('') : `
                <p class="text-sm text-gray-500" style="text-align: center; padding: 20px;">
                    Nenhuma avaliação ainda. Seja o primeiro a avaliar este parque!
                </p>
                `;

                // Dados simulados (REMOVIDOS - agora usamos dados reais)
                const simulatedParticipants = [
                    { photo: "src/assets/avatar1.png" },
                    { photo: "src/assets/avatar2.png" }
                ];

                const websiteLink = place.website ?
                    `<i class="fa-solid fa-globe"></i> <a href="${place.website}" target="_blank" style="color:#7CBD64;">Site: ${new URL(place.website).hostname}</a>` :
                    `<i class="fa-solid fa-globe"></i> <span>Site: Não disponível</span>`;

                const participantsHtml = simulatedParticipants.map(p => `
                    <img src="${p.photo || ''}"
                    class="participant-photo"
                    onerror="this.onerror=null; this.src='${PLACEHOLDER_AVATAR_URL}';"
                    alt="Foto do Participante"
                    >
                    `).join('');

                // --- NOVA LÓGICA DE FILTRAGEM E EXIBIÇÃO DE AMENIDADES ---
                const amenities = PARK_AMENITIES_DATA; 
                
                // Filtra: mantém o botão de ação e as amenidades com 3 ou mais votos.
                const displayedAmenities = amenities.filter(item => {
                    if (item.type === 'action') return true; // Mantém o botão de ação "Adicionar sugestão"
                    
                    const count = counts[item.label] || 0;
                    return count >= 3; // <--- REGRA DE EXIBIÇÃO: CONTAGEM >= 3
                });

                // Garante que o botão 'Adicionar sugestão' seja exibido mesmo se não houver amenidades
                const amenitiesListForDisplay = displayedAmenities.length > 1 || (displayedAmenities.length === 1 && displayedAmenities[0].type === 'action')
                    ? displayedAmenities
                    : [amenities.find(a => a.type === 'action')];

                const amenitiesCarouselHtml = amenitiesListForDisplay.map(item => {

                    let onclickEvent = '';
                    let cursorStyle = '';
                    const amenityColor = item.color || '#ddd';

                    if (item.type === 'action') {
                        onclickEvent = `onclick="openAllAmenitiesSelectionScreen()"`;
                        cursorStyle = 'cursor: pointer;';
                    } else {
                        cursorStyle = '';
                    }

                    const wrapperStyle = `background-color: white; border: 4px solid ${amenityColor};`;

                    // Adiciona a contagem de votos (opcional, para visualização no carrossel)
                    const count = counts[item.label] || 0;
                    const countText = item.type !== 'action' ? 
                        `<span class="amenity-count" style="display:block; font-size: 0.7em; color: gray;">(${count} votos)</span>` : '';

                    return `
                    <div class="amenity-item" ${onclickEvent} style="${cursorStyle}">
                    <div class="amenity-icon-wrapper" style="${wrapperStyle}">
                    <img src="${item.icon}" alt="${item.label}" class="amenity-icon" />
                    </div>
                    <p class="amenity-label">${item.label}</p>
                    ${countText}
                    </div>
                    `;
                }).join('');
                // --- FIM DA LÓGICA DE AMENIDADES ---


                // --- INJEÇÃO DO CONTEÚDO HTML (content) ---
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
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <h3 class="section-title" style="margin: 0;">${reviewsCount} Avaliações</h3>
                    <button onclick="window.openAddReviewModal()" style="padding: 8px 15px; background-color: #5ED925; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9em; font-weight: bold;">
                        <i class="fa-solid fa-plus"></i> Avaliar
                    </button>
                </div>
                ${reviewsHtml}
                </div>
                `;
                // --- FIM DA INJEÇÃO DO CONTEÚDO HTML ---

                detailsContainer.innerHTML = content;
                detailsContainer.style.transition = 'transform 0.3s ease-out';
                detailsContainer.style.display = 'block';
                detailsContainer.style.transform = 'translateY(0)';

                const eventButton = document.getElementById(`create-event-button-${placeId}`);
                if (eventButton) {
                    eventButton.addEventListener('click', openCreateEventScreen);
                }

            }).catch(error => {
                // Trata erros durante o fetchAmenityCounts
                console.error("Erro ao carregar dados do parque após Places API:", error);
                // Renderiza o painel de detalhes com um erro nas amenidades
                detailsContainer.innerHTML = `
                    <div class="details-header" style="text-align: center;">
                    <h2>Detalhes do parque</h2>
                    </div>
                    <div class="park-content">
                    <h1 class="text-xl font-bold text-gray-800 mb-2">${title}</h1>
                    <p>Não foi possível carregar detalhes ou informações de amenidades. Tente novamente.</p>
                    </div>
                    `;
                detailsContainer.style.transition = 'transform 0.3s ease-out';
                detailsContainer.style.display = 'block';
                detailsContainer.style.transform = 'translateY(0)';
            }); 
            // --- FIM DA EXECUÇÃO APÓS PROMISE ---

        } else {
            // Lógica de erro do Google Places (mantida)
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




// ====================================================
// FUNÇÕES DE BACKEND (COMUNICAÇÃO COM O PHP)
// ====================================================

/**
 * Busca as contagens de votos de amenidades para um parque específico.
 * @param {string} placeId O ID do local (Place ID) do parque.
 * @returns {Promise<Object>} Um objeto contendo a contagem de votos, ex: { 'Bebedouro': 5, 'Banheiro': 2 }
 */
function fetchAmenityCounts(placeId) {
    // A URL é o seu endpoint PHP: /Spark-main/telas-mapas/src/javascript/park_amenities.php
    return fetch(`${PARK_AMENITIES_URL}?place_id=${placeId}`, { method: 'GET' })
        .then(response => {
            // Verifica se a resposta HTTP é OK (200)
            if (!response.ok) {
                // Tenta ler o erro do corpo da resposta, mas lança um erro HTTP se falhar
                return response.json().then(err => {
                    throw new Error(`Erro HTTP ${response.status}: ${err.message || 'Erro no servidor.'}`);
                }).catch(() => {
                    throw new Error(`Erro HTTP ${response.status}. Não foi possível buscar as amenidades.`);
                });
            }
            return response.json();
        })
        .then(data => {
            // Se o JSON for processado e não tiver 'success', ainda pode ser um erro lógico do PHP
            if (data.success) {
                // Armazena e retorna as contagens
                parkAmenityCounts = data.counts || {};
                return parkAmenityCounts;
            } else {
                console.error("Erro lógico ao buscar contagens:", data.message);
                return {}; // Retorna objeto vazio para não quebrar a exibição
            }
        })
        .catch(error => {
            console.error('Erro de rede ou na API de amenidades:', error);
            // Isso garante que o showDetailsPane não pare, mas apenas exiba o carrossel vazio
            return {}; 
        });
}


/**
 * Busca as avaliações de um parque específico.
 * @param {string} placeId O ID do local (Place ID) do parque.
 * @returns {Promise<Array>} Array com as avaliações do parque
 */
function fetchParkReviews(placeId) {
    return fetch(`${PARK_REVIEWS_URL}?place_id=${placeId}`, { method: 'GET' })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erro HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                return data.reviews || [];
            } else {
                console.error("Erro ao buscar avaliações:", data.message);
                return [];
            }
        })
        .catch(error => {
            console.error('Erro ao buscar avaliações:', error);
            return [];
        });
}


/**
 * Envia os votos do usuário para o backend e recarrega os detalhes do parque.
 */
window.handleSaveAmenities = function() {
    const parkId = window.currentParkPlaceId; 
    
    // Filtra as amenidades que o usuário marcou como presentes
    const selectedAmenities = parkAmenitiesState
        .filter(a => a.type === 'amenity' && a.isSelected)
        .map(a => a.label);

    // LOGS DE DEBUG
    console.log("=== DEBUG SAVE AMENITIES ===");
    console.log("Place ID:", parkId);
    console.log("Amenidades selecionadas:", selectedAmenities);
    console.log("===========================");

    if (!parkId) {
        alert("Erro ao salvar amenidades: ID do parque não encontrado.");
        console.error("Place ID ausente. Valor atual:", parkId);
        return;
    }

    if (selectedAmenities.length === 0) {
        alert("Por favor, selecione pelo menos uma amenidade antes de salvar.");
        return;
    }

    // Fecha o modal imediatamente para melhor UX
    const modal = document.getElementById('full-amenities-modal');
    if (modal) modal.remove();

    // Mostra mensagem de processamento
    console.log(`Enviando ${selectedAmenities.length} votos...`);

    // Envia um voto (upvote) para cada amenidade selecionada
    const promises = selectedAmenities.map(amenityName => {
        const formData = new FormData();
        formData.append('park_place_id', parkId);
        formData.append('amenity_name', amenityName);
        formData.append('action', 'upvote');

        console.log(`Votando em: ${amenityName}`);

        return fetch(PARK_AMENITIES_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log(`Resposta para ${amenityName}:`, data);
            return { amenityName, success: data.success, data };
        })
        .catch(error => {
            console.error(`Erro ao votar em ${amenityName}:`, error);
            return { amenityName, success: false, error: error.message };
        });
    });

    // Aguarda todas as requisições completarem
    Promise.all(promises)
        .then(results => {
            const successful = results.filter(r => r.success).length;
            const failed = results.filter(r => !r.success).length;

            console.log(`Resultados: ${successful} sucesso, ${failed} falhas`);

            if (successful > 0) {
                alert(`${successful} voto(s) registrado(s) com sucesso!`);
                
                // Recarrega o painel de detalhes para mostrar o estado atualizado
                showDetailsPane(parkId, window.currentParkName || currentParkName);
            } else {
                alert("Nenhum voto foi registrado. Verifique se você está logado.");
            }
        })
        .catch(error => {
            console.error('Erro geral ao processar votos:', error);
            alert("Erro ao processar os votos. Tente novamente.");
        });
};

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
