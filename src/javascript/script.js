let map;

function initMap() {
    console.log("initMap foi chamada");
    map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: -23.5505, lng: -46.6333 },
        zoom: 14
    });
}

window.initMap = initMap;


