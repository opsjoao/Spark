const autocomplete = new google.maps.places.Autocomplete(document.getElementById('pac-input'));
autocomplete.bindTo('bounds', map);

autocomplete.addListener('place_changed', function() {
    const place = autocomplete.getPlace();
    if (!place.geometry) return;

    map.setCenter(place.geometry.location);
    marker.setPosition(place.geometry.location);
    document.getElementById('latitude').value = place.geometry.location.lat();
    document.getElementById('longitude').value = place.geometry.location.lng();
});
