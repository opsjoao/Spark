let map, markers = [], autocomplete, placesService, infoWindow;

function initMap() {
  const fallbackLocal = { lat: -23.5505, lng: -46.6333 }; // SP

  map = new google.maps.Map(document.getElementById("map"), {
    center: fallbackLocal,
    zoom: 13
  });

  placesService = new google.maps.places.PlacesService(map);
  infoWindow = new google.maps.InfoWindow();

  const input = document.getElementById("pac-input");
  autocomplete = new google.maps.places.Autocomplete(input, {
    fields: ['name','geometry','types','formatted_address']
  });
  autocomplete.bindTo("bounds", map);

  autocomplete.addListener("place_changed", () => {
    const place = autocomplete.getPlace();
    if (!place.geometry || !place.geometry.location) {
      alert("Selecione uma opção.");
      return;
    }

    clearMarkers();

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
      // Create and show the details pane instead of using a separate fixed div
      showDetailsPane(placeId, title);
    });
  }

  markers.push(m);
}

// Function to handle showing the details pane
function showDetailsPane(placeId, title) {
  const request = {
    placeId: placeId,
    fields: ['name', 'formatted_address', 'website', 'formatted_phone_number', 'photos']
  };

  placesService.getDetails(request, (place, status) => {
    const detailsContainer = document.getElementById("park-details");
    
    if (status === google.maps.places.PlacesServiceStatus.OK) {
      let photoUrl = "";
      if (place.photos && place.photos.length > 0) {
        photoUrl = place.photos[0].getUrl({ maxWidth: 200 });
      }

      const content = `
        <img src="${photoUrl}" style="width:100%; max-height:160px; border-radius:10px; object-fit:cover; margin-bottom:10px;">
        <h3 style="text-align:center;margin:0;color:green;font-size:18px">${place.name}</h3>
        <div style="width: 100%; border-bottom: 0.1px solid #464646ff;margin-top:4px;margin-bottom:4px"></div>
        <p style="margin:5px 0 0;font-size:14px"><i class="fa-solid fa-location-dot"></i>  ${place.formatted_address || ""}</p>
        <p style="margin:5px 0 0;font-size:14px"><i class="fa-solid fa-phone"></i>  ${place.formatted_phone_number || "Não disponível"}</p>
        ${place.website ? `<a href="${place.website}" target="_blank" style="color:blue; display:block; margin-top:5px;"><i class="fa-solid fa-earth-americas"></i>  Visitar site</a>` : ""}
      `;
      detailsContainer.innerHTML = content;
      detailsContainer.style.display = 'block'; // Show the pane
    } else {
      detailsContainer.innerHTML = `<b>${title}</b><br>Não foi possível carregar detalhes.`;
      detailsContainer.style.display = 'block'; // Show the pane
    }
  });
}

// Listen for clicks on the entire document
document.addEventListener('click', (event) => {
  const detailsContainer = document.getElementById("park-details");
  const isClickInsidePane = detailsContainer.contains(event.target);
  const isClickOnMarker = event.target.tagName === 'IMG' && event.target.src.includes('pin_verde.png'); // A simple way to check if it's the marker icon

  // Hide the pane if the click is not inside the pane and not on a marker
  if (detailsContainer.style.display === 'block' && !isClickInsidePane && !isClickOnMarker) {
    detailsContainer.style.display = 'none';
  }
});


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



document.addEventListener("DOMContentLoaded", function() {
    const filterButton = document.getElementById("filter-menu-button");
    const filterMenu = document.getElementById("filter-menu");

    // Alterna a visibilidade do menu quando o botão é clicado
    filterButton.addEventListener("click", () => {
        // Usa o método toggle para adicionar/remover a classe 'filter-menu-visible'
        filterMenu.classList.toggle("filter-menu-visible");
        // Opcional: remove a classe 'filter-menu-hidden' quando a visibilidade é ativada
        filterMenu.classList.toggle("filter-menu-hidden");
    });

    // Opcional: Oculta o menu se o usuário clicar fora dele
    document.addEventListener("click", (event) => {
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
        // Coloque sua lógica de pesquisa aqui, usando o valor do `radiusInput`

        // Após a pesquisa, você pode esconder o menu
        filterMenu.classList.remove("filter-menu-visible");
        filterMenu.classList.add("filter-menu-hidden");
    });
});
