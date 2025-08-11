let map;
    let service;
    let marker;

    function initMap() {
      const localInicial = { lat: -23.5505, lng: -46.6333 }; // São Paulo
      map = new google.maps.Map(document.getElementById("map"), {
        center: localInicial,
        zoom: 13,
      });

      service = new google.maps.places.PlacesService(map);
    }

    window.initMap = initMap;

    function pesquisarParque() {
      const query = document.getElementById("place-input").value;
      if (!query) return;

      const request = {
        query: query,
        fields: ['name', 'geometry', 'formatted_address'],
      };

      service.findPlaceFromQuery(request, function(results, status) {
        if (status === google.maps.places.PlacesServiceStatus.OK && results[0]) {
          const place = results[0];

          // Move o mapa para o lugar encontrado
          map.setCenter(place.geometry.location);
          map.setZoom(15);

          // Remove marcador anterior
          if (marker) marker.setMap(null);

          // Adiciona marcador novo
          marker = new google.maps.Marker({
            map,
            position: place.geometry.location,
            title: place.name
          });

          console.log("Encontrado:", place.name, "-", place.formatted_address);
        } else {
          alert("Parque não encontrado.");
        }
      });
    }
    window.pesquisarParque = pesquisarParque;
