<?php
header('Content-Type: text/html; charset=utf-8');

$ini = parse_ini_file('../settings.ini', TRUE);

session_start(); if (!isset($_SESSION['user'])) { header('Location:login.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="en" style="height:100%;">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>112 Help - Backend</title>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/admin.css">
  </head>

  <body style="height:100%;">

    <nav class="navbar navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <img src="logo.png" alt="112Help">
        </div>
      </div>
    </nav>

    <div class="container-fluid" style="height:100%;">
      <div class="row" style="height:100%;">
        <div class="col-sm-2 col-md-1 sidebar">
          <ul class="nav nav-sidebar">
            <li><a href="index.php">Real Time</a></li>
            <li class="active"><a href="map.php">Map</a></li>
            <li><a href="twitter.php">Twitter Feed</a></li>
          </ul>
        </div>
        <div class="col-sm-10 col-sm-offset-2 col-md-11 col-md-offset-1 main" style="height:100%;">
          <div id="map"></div>
        </div>
      </div>
    </div>
    <script type="text/javascript">
    var map, circles = new Array();
    function initMap() {
<?php if (isset($_GET['lat'], $_GET['lng'])) { ?>
      var options = {
        center: {lat: <?= floatval($_GET['lat']) ?>, lng: <?= floatval($_GET['lng']) ?>},
        zoom: 18
      };
<?php } else { ?>
      var options = {
        center: {lat: 50.855545, lng: 4.341233},
        zoom: 15
      };
<?php } ?>

      map = new google.maps.Map(document.getElementById('map'), options);

<?php
$mysqli = new MySQLi($ini['mysql']['host'], $ini['mysql']['username'], $ini['mysql']['passwd'], $ini['mysql']['dbname'], $ini['mysql']['port']);
$mysqli->set_charset('utf8');
$q = $mysqli->query("SELECT `id`, `accuracy`, `indanger`, X(`position`) AS `lng`, Y(`position`) AS `lat` FROM `help` /*WHERE `datetime` >= '".date('Y-m-d H:is', time()-24*60*60)."'*/ ORDER BY `datetime` DESC") or trigger_error($mysqli->error);
while ($r = $q->fetch_assoc()) {
?>
    circles[<?= $r['id'] ?>] = new google.maps.Circle({
      strokeColor: "<?= ($r['indanger'] == 1 ? '#f00' : '#bbb') ?>",
      strokeWeight: 1,
      fillColor: "<?= ($r['indanger'] == 1 ? '#f00' : '#bbb') ?>",
      fillOpacity: 0,
      map: map,
      center: {lat: <?= $r['lat'] ?>, lng: <?= $r['lng'] ?>},
      radius: <?= $r['accuracy'] ?>
    });
<?php
  if (isset($_GET['id']) && $_GET['id'] == $r['id']) {
?>
    map.fitBounds(circles[<?= $r['id'] ?>].getBounds());
    circles[<?= $r['id'] ?>].setOptions({
      strokeWeight: 5,
      fillOpacity: 0.1,
    });
<?php
  }
}
$q->free();
$mysqli->close();
?>
    }

    </script>
    <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyD_p27IkNE2nxfTCtuf5oxyGUsmz4R7i34&callback=initMap"></script>
  </body>
</html>