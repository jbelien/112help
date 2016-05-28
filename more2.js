var init = function() {
  var geocoder = new google.maps.Geocoder;
  geocoder.geocode({ 'location': { lat: position.lat, lng: position.lng } }, function(results, status) {
    console.log(position, results, status);
    if (status === google.maps.GeocoderStatus.OK) {
      document.getElementsByClassName('adresse')[0].innerText = results[0].formatted_address;
    }
  });

  document.getElementsByClassName('btn-reseaux-sociaux')[0].onclick = function() {
    document.body.classList.add('message');
  };
  document.getElementsByClassName('btn-cancel')[0].onclick = function() {
    document.body.classList.remove('message');
  };
}