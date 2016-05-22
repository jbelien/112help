<?php
header('Content-Type: text/html; charset=utf-8');

$ini = parse_ini_file('settings.ini', TRUE);

switch ($_SERVER['HTTP_HOST']) {
  case '112help.be': $lang = 'en'; break;
  case '112hulp.be': $lang = 'nl'; break;
  case '112aide.be': $lang = 'fr'; break;
  default:
    $languages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
    $lang = strtolower(substr(current($languages), 0, 2));
    break;
}

if ($lang == 'fr' || $lang == 'nl') {
  putenv('LC_TIME='.$lang.'_BE.UTF-8');
  putenv('LC_MESSAGES='.$lang.'_BE.UTF-8');
  setlocale(LC_TIME, $lang.'_BE.UTF-8');
  if (defined('LC_MESSAGES')) setlocale(LC_MESSAGES, $lang.'_BE.UTF-8');
}
else {
  putenv('LC_ALL=en_US.UTF-8');
  setlocale(LC_ALL, 'en_US.UTF-8');
}
bindtextdomain('112help', __DIR__.'/locale');
bind_textdomain_codeset('112help', 'UTF-8');
textdomain('112help');

session_start();

if (isset($_GET['name' ])) $_SESSION['name' ] = trim(urldecode($_GET['name' ])); else if (isset($_SESSION['name' ])) unset($_SESSION['name' ]);
if (isset($_GET['phone'])) $_SESSION['phone'] = trim(urldecode($_GET['phone'])); else if (isset($_SESSION['phone'])) unset($_SESSION['phone']);

if (isset($_POST['action'], $_POST['lat'], $_POST['lng']) && $_POST['action'] == 'send') {
  $headers = (function_exists('apache_request_headers') ? apache_request_headers() : $_SERVER);
  if ( array_key_exists( 'X-Forwarded-For', $headers ) && filter_var( $headers['X-Forwarded-For'], FILTER_VALIDATE_IP ) ) {
    $forward = $headers['X-Forwarded-For'];
  }
  else if ( array_key_exists( 'HTTP_X_FORWARDED_FOR', $headers ) && filter_var( $headers['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP ) ) {
    $forward = $headers['HTTP_X_FORWARDED_FOR'];
  }

  $sh = shell_exec('whois '.(isset($forward) ? $forward : $_SERVER['REMOTE_ADDR']));

  $mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
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
    <link href="/css/style.css" rel="stylesheet">
  </head>
  <body>
  <div class="container">
    <h1 id="logo" style="margin-bottom:0;"><?= strtoupper($_SERVER['HTTP_HOST']) ?></h1>
    <h2 style="color:#ffff00; margin:0;">** PROTOTYPE **</h2>
    <h1 style="font-size:1.5em;"><?= _('I\'M IN DANGER AND I NEED HELP') ?>*</h1>
    <p id="error" style="color:#f00; font-weight:bold;"></p>
    <form id="location" action="/index.php" method="post" autocomplete="off">
      <input type="hidden" name="batt" id="batt" value="-1" readonly="readonly">
      <input type="hidden" name="time" id="time" readonly="readonly">
<?php if (isset($_SESSION['name'])) { ?>
      <div>
        <label for="name"><?= _('Name') ?> :</label>
        <?= (isset($_SESSION['name' ]) ? '<input type="text" name="name" id="name" value="'.htmlentities($_SESSION['name' ]).'" readonly="readonly">'.PHP_EOL : '') ?>
      </div>
<?php } ?>
<?php if (isset($_SESSION['phone'])) { ?>
      <div>
        <label for="phone"><?= _('Phone') ?> :</label>
        <?= (isset($_SESSION['phone']) ? ' <input type="text" name="phone" id="phone" value="'.htmlentities($_SESSION['phone']).'" readonly="readonly">'.PHP_EOL : '') ?>
      </div>
<?php } ?>
      <p id="loading"><?= _('Requesting access / Loading location data...') ?></p>
      <div id="geolocation" style="display:none;">
        <div>
          <label for="lat"><?= _('Latitude') ?> :</label>
          <input type="text" name="lat" id="lat" readonly="readonly">
        </div>
        <div>
          <label for="lng"><?= _('Longitude') ?> :</label>
          <input type="text" name="lng" id="lng" readonly="readonly">
        </div>
        <div>
          <label for="acc"><?= _('Accuracy') ?> (m.) :</label>
          <input type="text" name="acc" id="acc" readonly="readonly">
        </div>
        <div>
          <label><?= _('Address') ?> :</label><br>
          <address id="addr"></address>
        </div>
        <div id="send">
          <button type="submit" name="action" value="send"><?= _('SEND MY LOCATION') ?></button>
        </div>
      </div>
    </form>
    <p class="legal">* <?= _('Irresponsible use of this emergency service is punishable under federal law.') ?></p>
  </div>
  <script>
    function init() {
      if ('geolocation' in navigator) {
        navigator.geolocation.watchPosition(function(position) {
          document.getElementById('loading').style.display = 'none';
          document.getElementById('geolocation').style.display = '';

          console.log(position);

          document.getElementById('time').value = position.timestamp;
          document.getElementById('lat').value = Math.round(position.coords.latitude  * 1000000) / 1000000;
          document.getElementById('lng').value = Math.round(position.coords.longitude * 1000000) / 1000000;
          document.getElementById('acc').value = Math.round(position.coords.accuracy * 10) / 10;

          var geocoder = new google.maps.Geocoder;
          geocoder.geocode({'location': {lat: position.coords.latitude, lng: position.coords.longitude} }, function(results, status) {
            //console.log(results, status);
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
          //console.log(battery);
          document.getElementById('batt').value = battery.level;
        });
      }
    }
  </script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD_p27IkNE2nxfTCtuf5oxyGUsmz4R7i34&amp;language=<?= $lang ?>&amp;callback=init" async defer></script>
  </body>
</html>