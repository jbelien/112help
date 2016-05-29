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