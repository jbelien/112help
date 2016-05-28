(function() {
  if ('geolocation' in navigator) {
    document.getElementsByClassName('container-footer')[0].classList.add('active');
    navigator.geolocation.watchPosition(function(position) {
      document.getElementsByClassName('container-footer')[0].classList.remove('active');
      document.getElementById('btn-send').classList.add('active');
      document.getElementById('btn-send').disabled = null;

      console.log(position);

      document.getElementById('time').value = position.timestamp;
      document.getElementById('lat').value = Math.round(position.coords.latitude  * 1000000) / 1000000;
      document.getElementById('lng').value = Math.round(position.coords.longitude * 1000000) / 1000000;
      document.getElementById('acc').value = Math.round(position.coords.accuracy * 10) / 10;

      /*
      var geocoder = new google.maps.Geocoder;
      geocoder.geocode({'location': {lat: position.coords.latitude, lng: position.coords.longitude} }, function(results, status) {
        //console.log(results, status);
        if (status === google.maps.GeocoderStatus.OK) {
          document.getElementById('addr').innerText = results[0].formatted_address;
        }
      });
      */
    },
    function(error) {
      document.getElementsByClassName('container-footer')[0].classList.remove('active');
      var modal = document.getElementsByClassName('modal-container')[0];
      modal.classList.add('active');
      modal.getElementsByClassName('description')[0].innerText = error.message;
    },
    {
      enableHighAccuracy: true,
      timeout: 10*1000,
      maximumAge: 5*60*1000
    });
  } else {
    document.getElementsByClassName('container-footer')[0].classList.remove('active');
    var modal = document.getElementsByClassName('modal-container')[0];
    modal.classList.add('active');
    modal.getElementsByClassName('description')[0].innerText = 'Geolocation service is not available on this device.';
  }

  if ('battery' in navigator) {
    navigator.getBattery().then(function(battery) {
      console.log(battery);
      document.getElementById('batt').value = battery.level;
    });
  }
})(window);
