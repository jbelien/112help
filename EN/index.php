<?php
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Europe/Brussels');

session_start();

if (isset($_GET['name' ])) $_SESSION['name' ] = trim($_GET['name' ]);
if (isset($_GET['phone'])) $_SESSION['phone'] = trim($_GET['phone']);

if (isset($_POST['action'], $_POST['lat'], $_POST['lng']) && $_POST['action'] == 'send') {
  $headers = (function_exists('apache_request_headers') ? apache_request_headers() : $_SERVER);
  if ( array_key_exists( 'X-Forwarded-For', $headers ) && filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP ) ) {
    $forward = $headers['X-Forwarded-For'];
  }
  else if ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) && filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP ) ) {
    $forward = $headers['HTTP_X_FORWARDED_FOR'];
  }

  $sh = shell_exec('whois '.(isset($forward) ? $forward : $_SERVER['REMOTE_ADDR']));

  $mysqli = new MySQLi('localhost', 'riikc_112', '2c4;i+mu7CJ;', 'riikc_help');
  $mysqli->set_charset('utf8');

  $qsz  = "INSERT INTO `help` (";
  $qsz .= " `datetime`";
  $qsz .= ",`position`";
  $qsz .= ",`accuracy`";
  $qsz .= ",`battery`";
  $qsz .= ",`ip`";
  $qsz .= ",`ip_forwarded`";
  $qsz .= ",`whois`";
  $qsz .= ",`name`";
  $qsz .= ",`phone`";
  $qsz .= ") VALUES (";
  $qsz .= " '".date('Y-m-d H:i:s', round(intval($_POST['time']) / 1000))."'";
  $qsz .= ",GeomFromText(CONCAT('POINT(".floatval($_POST['lng'])." ".floatval($_POST['lat']).")'))";
  $qsz .= ",".floatval($_POST['acc']);
  $qsz .= ",".($_POST['batt'] != -1 ? floatval($_POST['batt']) * 100 : "NULL");
  $qsz .= ",'".$_SERVER['REMOTE_ADDR']."'";
  $qsz .= ",".(isset($forward) ? "'".$mysqli->real_escape_string($forward)."'" : "NULL");
  $qsz .= ",'".$mysqli->real_escape_string($sh)."'";
  $qsz .= ",".(isset($_POST['name']) ? "'".$mysqli->real_escape_string($_POST['name'])."'" : "NULL");
  $qsz .= ",".(isset($_POST['phone']) ? "'".$mysqli->real_escape_string($_POST['phone'])."'" : "NULL");
  $qsz .= ")";

  $mysqli->query($qsz) or trigger_error($mysqli->error);

  $_SESSION['id'] = $mysqli->insert_id;

  $mysqli->close();

  header('Location:info.php');
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>112 Help</title>
    <link href="/style.css" rel="stylesheet">
  </head>
  <body>
  <div id="content">
    <div id="logo">112HELP.be</div>
    <h1>I'M IN DANGER AND I NEED HELP*</h1>
    <p style="color:#f00; font-size:0.8em;">Warning: this <span style="text-decoration:underline;">prototype</span> will only work on <strong>Google Chrome</strong> through an encrypted connection.</p>
    <p id="error" style="color:#f00; font-weight:bold;"></p>
    <form id="form" action="/index.php" method="post" autocomplete="off">
      <input type="hidden" name="batt" id="batt" value="-1" readonly="readonly">
      <input type="hidden" name="time" id="time" readonly="readonly">
<?php if (isset($_SESSION['name'])) { ?>
      <div>
        <label for="name">Name :</label>
        <?= (isset($_SESSION['name' ]) ? '<input type="text" name="name" id="name" value="'.htmlentities($_SESSION['name' ]).'" readonly="readonly">'.PHP_EOL : '') ?>
      </div>
<?php } ?>
<?php if (isset($_SESSION['phone'])) { ?>
      <div>
        <label for="phone">Phone :</label>
        <?= (isset($_SESSION['phone']) ? ' <input type="text" name="phone" id="phone" value="'.htmlentities($_SESSION['phone']).'" readonly="readonly">'.PHP_EOL : '') ?>
      </div>
<?php } ?>
      <p id="loading">Requesting access /Loading location data ...</p>
      <div id="geolocation" style="display:none;">
        <div>
          <label for="lat">Latitude :</label>
          <input type="text" name="lat" id="lat" readonly="readonly">
        </div>
        <div>
          <label for="lng">Longitude :</label>
          <input type="text" name="lng" id="lng" readonly="readonly">
        </div>
        <div>
          <label for="acc">Precision (m.) :</label>
          <input type="text" name="acc" id="acc" readonly="readonly">
        </div>
        <div>
          <label>Address :</label><br>
          <address id="addr"></address>
        </div>
        <div id="send">
          <button type="submit" name="action" value="send">SEND MY LOCATION</button>
        </div>
      </div>
    </form>
    <p class="legal">* Irresponsible use of this emergency service is punishable under federal law.</p>
  </div>
  <script>
    function init() {
      // Chrome : need HTTPS : https://www.chromium.org/Home/chromium-security/prefer-secure-origins-for-powerful-new-features
      if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(function(position) {
          document.getElementById('loading').style.display = 'none';
          document.getElementById('geolocation').style.display = '';

          console.log(position);

          document.getElementById('time').value = position.timestamp;
          document.getElementById('lat').value = Math.round(position.coords.latitude  * 1000000) / 1000000;
          document.getElementById('lng').value = Math.round(position.coords.longitude * 1000000) / 1000000;
          document.getElementById('acc').value = Math.round(position.coords.accuracy * 10) / 10;

          var geocoder = new google.maps.Geocoder;
          geocoder.geocode({'location': {lat: position.coords.latitude, lng: position.coords.longitude} }, function(results, status) {
            console.log(results, status);
            if (status === google.maps.GeocoderStatus.OK) {
              document.getElementById('addr').innerText = results[0].formatted_address;
            }
          });
        },
        function(error) {
          document.getElementById('loading').remove();
          document.getElementById('error').innerText = error.message;
        },
        {
          enableHighAccuracy: true,
          timeout: 10*1000,
          maximumAge: 5*60*1000
        });
      } else {
        document.getElementById('loading').style.display = 'none';
        document.getElementById('error').innerText = 'Le service de géolocalisation n\'est pas disponible sur votre ordinateur.';
      }

      if ('battery' in navigator) {
        navigator.getBattery().then(function(battery) {
          console.log(battery);
          document.getElementById('batt').value = battery.level;
        });
      }
    }
  </script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD_p27IkNE2nxfTCtuf5oxyGUsmz4R7i34&callback=init" async defer></script>
  </body>
</html>
<!--
<?php
$sh = shell_exec('whois 174.116.185.44'); var_dump($sh);
?>
-->