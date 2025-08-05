    let map;
    let service;
    let infowindow;



var address = document.getElementById("address").value;
var geocoder = new google.maps.Geocoder();

geocoder.geocode( { 'address': address}, function(results, status) {
  var location = results[0].geometry.location;
  alert(location.lat() + '' + location.lng());
});



        function getAddressFromCoordinates(lat, lng) {
        const geocoder = new google.maps.Geocoder();
        const latlng = { lat: parseFloat(lat), lng: parseFloat(lng) };

        geocoder.geocode({ location: latlng }, (results, status) => {
            if (status === "OK") {
            if (results[0]) {
                console.log("Endereço:", results[0].formatted_address);
            } else {
                console.log("Nenhum resultado encontrado.");
            }
            } else {
            console.log("Geocoder falhou devido a: " + status);
            }
        });

    function initMap() {
      map = new google.maps.Map(document.getElementById('map'), {
        zoom: 14
      });
    }
        const service = new google.maps.places.PlacesService(map);
        service.nearbySearch({
        location: {lat: lat, lng: lng },
        radius: 1500,
        type: ['restaurant']  // pode ser 'school', 'store', 'hospital', etc.
        }, (results, status) => {
        if (status === google.maps.places.PlacesServiceStatus.OK) {
            results.forEach(place => {
            console.log(place.name);
            });
        }
        });


      // Faz a pesquisa na API Places
      service.nearbySearch(request, (results, status) => {
        if (status === google.maps.places.PlacesServiceStatus.OK) {
          for (let i = 0; i < results.length; i++) {
            const place = results[i];
            createMarker(place);
          }
        }
      });
    }

    // Cria um marcador para cada lugar encontrado
    function createMarker(place) {
      const marker = new google.maps.Marker({
        map: map,
        position: place.geometry.location
      });

      // Adiciona um evento de clique no marcador
      google.maps.event.addListener(marker, 'click', () => {
        infowindow.setContent(place.name);
        infowindow.open(map, marker);
      });
    }