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
    if (detailsContainer) {
        detailsContainer.style.display = 'none';
        detailsContainer.style.transform = 'translateY(0)';
        detailsContainer.style.transition = 'none'; // Desliga a transição ao esconder
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
    // Verifica se o usuário está tentando rolar o conteúdo
    if (detailsContainer.scrollHeight > detailsContainer.clientHeight && detailsContainer.scrollTop > 0) {
        return; // Permite rolar o conteúdo normalmente
    }

    isDragging = true;
    wasDragged = false; // IMPORTANTE: Reseta o rastreio de arrasto no início
    // Pega a posição Y do clique (mouse) ou do primeiro toque (touch)
    startY = event.type.startsWith('touch') ? event.touches[0].clientY : event.clientY;
    
    // Desliga a transição para que o arrasto seja imediato
    detailsContainer.style.transition = 'none'; 
    detailsContainer.style.cursor = 'grabbing';
    
    // Calcula a translação inicial
    const style = window.getComputedStyle(detailsContainer);
    const transform = style.getPropertyValue('transform');
    const match = transform.match(/matrix\(1, 0, 0, 1, 0, (.*)\)/);
    initialTranslateY = match ? parseFloat(match[1]) : 0;
}

function drag(event) {
    if (!isDragging) return;

    // Previne o scroll da página ou outros comportamentos padrão
    event.preventDefault(); 
    
    const currentY = event.type.startsWith('touch') ? event.touches[0].clientY : event.clientY;
    let diffY = currentY - startY;
    
    // Se o movimento vertical for maior que o limite, marca como arrasto
    if (Math.abs(diffY) > DRAG_THRESHOLD) {
        wasDragged = true;
    }

    // Garante que só se pode arrastar para baixo (diffY >= 0)
    if (diffY < 0) {
        diffY = 0;
    }

    // Move o painel aplicando a translação Y
    detailsContainer.style.transform = `translateY(${initialTranslateY + diffY}px)`;
}

function endDrag(event) {
    if (!isDragging) return;
    
    isDragging = false;
    detailsContainer.style.transition = 'transform 0.3s ease-out'; // Liga a transição novamente
    detailsContainer.style.cursor = 'grab';

    // Pega a translação Y final para decidir o que fazer
    const style = window.getComputedStyle(detailsContainer);
    const transform = style.getPropertyValue('transform');
    const match = transform.match(/matrix\(1, 0, 0, 1, 0, (.*)\)/);
    const finalTranslateY = match ? parseFloat(match[1]) : 0;
    
    // Limite para fechar (25% da altura do container)
    const threshold = detailsContainer.offsetHeight * 0.25; 

    if (finalTranslateY > threshold) {
        // Arrastou o suficiente: esconde com animação
        detailsContainer.style.transform = `translateY(${detailsContainer.offsetHeight + 20}px)`;
        // Oculta completamente após a animação
        setTimeout(hideDetailsPane, 300); 
    } else {
        // Não arrastou o suficiente: retorna para a posição original
        detailsContainer.style.transform = 'translateY(0)';
    }
    // Não resetamos wasDragged aqui. Ele será resetado no setupClickToClose.
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
        
        // Trata o caso onde o clique é feito em qualquer outro lugar que não seja um dos marcadores
        // e queremos fechar o painel.
    });
}

// ----------------------------------------------------
// Função showDetailsPane (Mantida)
// ----------------------------------------------------
function showDetailsPane(placeId, title) {
    const request = {
        placeId: placeId,
        fields: ['name', 'formatted_address', 'website', 'formatted_phone_number', 'photos']
    };

    placesService.getDetails(request, (place, status) => {
        detailsContainer = document.getElementById("park-details"); 

        if (status === google.maps.places.PlacesServiceStatus.OK) {
            let photoUrl = "";
            if (place.photos && place.photos.length > 0) {
                photoUrl = place.photos[0].getUrl({ maxWidth: 200 });
            }

            // Adicionei um "handle" visual (a linha cinza) para indicar que é arrastável
            const content = `
                <div style="text-align:center; margin-bottom: 10px; cursor: grab;">
                   <div style="width: 40px; height: 4px; background-color: #ccc; margin: 0 auto; border-radius: 2px;"></div>
                </div>
                <img src="${photoUrl}" style="width:100%; max-height:200px; border-radius:10px; object-fit:cover; margin-bottom:10px;">
                <h3 style="text-align:center;margin:0;color:green;font-size:18px">${place.name}</h3>
                <div style="width: 100%; border-bottom: 0.1px solid #464646ff;margin-top:4px;margin-bottom:4px"></div>
                <p style="margin:5px 0 0;font-size:14px"><i class="fa-solid fa-location-dot"></i>  ${place.formatted_address || ""}</p>
                <p style="margin:5px 0 0;font-size:14px"><i class="fa-solid fa-phone"></i>  ${place.formatted_phone_number || "Não disponível"}</p>
                ${place.website ? `<a href="${place.website}" target="_blank" style="color:blue; display:block; margin-top:5px;"><i class="fa-solid fa-earth-americas"></i>  Visitar site</a>` : ""}
            `;
            detailsContainer.innerHTML = content;
            detailsContainer.style.transition = 'transform 0.3s ease-out';
            detailsContainer.style.transform = 'translateY(0)';
            detailsContainer.style.display = 'block'; 
        } else {
            detailsContainer.innerHTML = `<b>${title}</b><br>Não foi possível carregar detalhes.`;
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
