var map, geocoder, autocomplete, marker;
function initMap() {
    geocoder = new google.maps.Geocoder;

    map = new google.maps.Map(document.getElementById('map'), {
        center: (position != null ? position : { lat: 50.6678944, lng: 4.7102403 }),
        zoom: (position != null ? 16 : 8),
        streetViewControl: false,
        mapTypeControlOptions: { style: google.maps.MapTypeControlStyle.DROPDOWN_MENU }
    });
    map.addListener('click', function(event) {
        if (typeof(marker) == 'undefined') {
            marker = new google.maps.Marker({ map: map, position: event.latLng });
        } else {
            marker.setPosition(event.latLng);
        }

        map.setCenter(event.latLng);
        if (map.getZoom() < 15) map.setZoom(15);

        geocoder.geocode({ 'location': event.latLng }, function(results, status) {
            console.log(event.latLng.toString(), results, status);

            if (status === google.maps.GeocoderStatus.OK) {
                document.getElementById('adresse').value = results[0].formatted_address;
                document.getElementById('lat').value = results[0].geometry.location.lat();
                document.getElementById('lng').value = results[0].geometry.location.lng();
            }
        });
    });

    if (position != null) {
        marker = new google.maps.Marker({ map: map, position: position });
    }

    autoComplete = new google.maps.places.Autocomplete(document.getElementById('adresse'), {
        types: ['geocode'],
        componentRestrictions: { country: 'be' }
    });

    google.maps.event.addListener(autoComplete, 'place_changed', function() {
        var place = autoComplete.getPlace();

        if (typeof(marker) == 'undefined') {
            marker = new google.maps.Marker({ map: map, position: place.geometry.location });
        } else {
            marker.setPosition(place.geometry.location);
        }

        document.getElementById('lat').value = place.geometry.location.lat();
        document.getElementById('lng').value = place.geometry.location.lng();

        map.setCenter(place.geometry.location);
        if (map.getZoom() < 15) map.setZoom(15);
    });

    if ('battery' in navigator) {
        navigator.getBattery().then(function(battery) {
            console.log(battery);
            document.getElementById('batt').value = battery.level;
        });
    }

    document.getElementsByClassName('texte-validation')[0].onclick = function(event) {
        event.preventDefault();
        document.getElementById('form-address').submit();
    };

    document.getElementsByClassName('annulation-alert')[0].onclick = function() {
        document.getElementsByClassName('modal-container')[0].classList.add('active');
    };
    document.getElementsByClassName('up-action')[0].onclick = function(event) {
        event.preventDefault();
        document.location.href = '/index.php?clear';
    };
    document.getElementsByClassName('down-action')[0].onclick = function(event) {
        event.preventDefault();
        document.getElementsByClassName('modal-container')[0].classList.remove('active');
    };
}
