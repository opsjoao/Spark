let map, markers = [], autocomplete, placesService;

    function initMap(){
      const fallbackLocal = { lat: -23.5505, lng: -46.6333 }; // SP

      map = new google.maps.Map(document.getElementById("map"), {
        center: fallbackLocal,
        zoom: 13
      });

      placesService = new google.maps.places.PlacesService(map);

      // Autocomplete para qualquer tipo de local
      const input = document.getElementById("pac-input");
      autocomplete = new google.maps.places.Autocomplete(input, {
        fields: ['name','geometry','types','formatted_address']
      });
      autocomplete.bindTo("bounds", map);

      // Evento ao selecionar um local
      autocomplete.addListener("place_changed", () => {
        const place = autocomplete.getPlace();
        if (!place.geometry || !place.geometry.location) {
          alert("Selecione uma opcao.");
          return;
        }

        clearMarkers();

        if (place.types && place.types.includes("park")) {
          // Caso seja um parque
          addMarker(place.geometry.location, place.name);
          map.setCenter(place.geometry.location);
          map.setZoom(15);
        } else {
          // Caso seja uma região/local → buscar parques próximos
          buscarParquesProximos(place.geometry.location);
        }
      });

      // GPS inicial
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (pos) => {
            const userPos = { lat: pos.coords.latitude, lng: pos.coords.longitude };
            map.setCenter(userPos);
            addMarker(userPos, "Você está aqui!");
          },
          () => console.warn("GPS não permitido.")
        );
      }
    }

    function buscarParquesProximos(localizacao){
      const request = {
        location: localizacao,
        radius: 5000, // 5 km
        type: 'park'
      };

      placesService.nearbySearch(request, (results, status) => {
        if (status === google.maps.places.PlacesServiceStatus.OK && results.length) {
          const bounds = new google.maps.LatLngBounds();
          results.forEach(r => {
            addMarker(r.geometry.location, r.name);
            bounds.extend(r.geometry.location);
          });
          map.fitBounds(bounds);
        } else {
          alert("Nenhum parque encontrado nessa região.");
        }
      });
    }

    function addMarker(position, title){
      const m = new google.maps.Marker({ map, position, title });
      markers.push(m);
    }

    function clearMarkers(){
      markers.forEach(m => m.setMap(null));
      markers = [];
    }

    window.initMap = initMap;


