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

  // Ao clicar no marcador, buscar detalhes do lugar pelo place_id
  if (placeId) {
    m.addListener("click", () => {
      const request = {
        placeId: placeId,
        fields: ['name', 'formatted_address', 'website', 'formatted_phone_number', 'photos']
      };

      placesService.getDetails(request, (place, status) => {
        if (status === google.maps.places.PlacesServiceStatus.OK) {
          let photoUrl = "";
          if (place.photos && place.photos.length > 0) {
            photoUrl = place.photos[0].getUrl({maxWidth: 200});
          }

// Criar a div com conteúdo
const content = `
  <div id="mapContent" style="position: fixed; top: 0; left: 0; width: 50%; height: 100vh; font-family: Arial; overflow-y: auto; z-index: 1000; padding: 10px; background-color: white; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);">
    <img src="${photoUrl}" style="width:100%; max-height:160px; border-radius:10px; object-fit:cover; margin-bottom:10px;">
    <h3 style="margin:0;color:green;">${place.name}</h3>
    <p style="margin:5px 0 0;">${place.formatted_address || ""}</p>
    <p style="margin:5px 0 0;"><b>Telefone:</b> ${place.formatted_phone_number || "Não disponível"}</p>
    ${place.website ? `<a href="${place.website}" target="_blank" style="color:blue; display:block; margin-top:5px;">Visitar site</a>` : ""}
  </div>
`;

// Inserir o conteúdo na página
document.body.innerHTML += content;

// Função para fechar a div quando clicar fora
function closeOnClickOutside(event) {
  const contentDiv = document.getElementById('mapContent');
  // Verifica se o clique foi fora da div
  if (contentDiv && !contentDiv.contains(event.target)) {
    contentDiv.style.display = 'none';  // Fecha a div
    document.removeEventListener('click', closeOnClickOutside);  // Remove o evento de clique
  }
}

// Adicionar o evento de clique para fechar a div
document.addEventListener('click', closeOnClickOutside);

// Função para reabilitar o clique no mapa, após fechar a div
function reenableClickOnMap() {
  const contentDiv = document.getElementById('mapContent');
  contentDiv.style.display = 'none';  // Fecha a div
  // Adiciona o ouvinte de novo, permitindo interagir com o mapa
  document.addEventListener('click', closeOnClickOutside);
}



          infoWindow.setContent(content);
          infoWindow.open(map, m);
        } else {
          infoWindow.setContent(`<b>${title}</b><br>Não foi possível carregar detalhes.`);
          infoWindow.open(map, m);
        }
      });
    });
  }

  markers.push(m);
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
