<?php
header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('Europe/Brussels');

include('proj4php-2.0.7/vendor/autoload.php');

use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;

// Initialise Proj4
$proj4 = new Proj4php();

// Create two different projections.
$projLMBB   = new Proj('EPSG:31370', $proj4);
$projWGS84  = new Proj('EPSG:4326' , $proj4);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>112 Help - Backend</title>
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
    <link rel="stylesheet" href="admin.css">
  </head>

  <body>

    <nav class="navbar navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <img src="logo.png" alt="112Help">
        </div>
      </div>
    </nav>

    <div class="container-fluid">
      <div class="row">
        <div class="col-sm-2 col-md-1 sidebar">
          <ul class="nav nav-sidebar">
            <li class="active"><a href="admin.php">Real Time</a></li>
            <li><a href="map.php">Map</a></li>
            <li><a href="twitter.php">Twitter Feed</a></li>
          </ul>
        </div>
        <div class="col-sm-10 col-sm-offset-2 col-md-11 col-md-offset-1 main">
          <table class="table table-condensed table-striped table-bordered small">
            <thead>
              <tr>
                <th>Datetime</th>
                <th>Name</th>
                <th>Nr</th>
                <th>Address</th>
                <th>Position (lat, lon)</th>
                <th>Accuracy (m.)</th>
                <th>Frequency by location (1 km&sup2;)</th>
                <th>Battery level</th>
                <th>Description</th>
                <th>Emergency type</th>
                <th>I don't need help anymore</th>
                <th>Netname</th>
                <th>IP</th>
              </tr>
            </thead>
            <tbody>
<?php
$mysqli = new MySQLi('localhost', '112help_cHeca7ru', 'Z7j5CesTephudRes', '112help');
$mysqli->set_charset('utf8');
$q = $mysqli->query("SELECT *, X(`position`) AS `lng`, Y(`position`) AS `lat` FROM `help` /*WHERE `datetime` >= '".date('Y-m-d H:is', time()-24*60*60)."'*/ ORDER BY `datetime` DESC") or trigger_error($mysqli->error);
$i = 0; while ($r = $q->fetch_assoc()) {
  $xy = $proj4->transform($projLMBB , new Point($r['lng'], $r['lat'], $projWGS84));
  $xy_min = new Point(($xy->x - 500), ($xy->y - 500), $projLMBB); $ll_min = $proj4->transform($projWGS84, $xy_min);
  $xy_max = new Point(($xy->x + 500), ($xy->y + 500), $projLMBB); $ll_max = $proj4->transform($projWGS84, $xy_max);

  $qsz = "SELECT COUNT(*) FROM `help` WHERE `datetime` >= '".date('Y-m-d H:is', time()-24*60*60)."' AND X(`position`) > ".$ll_min->x." AND X(`position`) < ".$ll_max->x." AND Y(`position`) > ".$ll_min->y." AND Y(`position`) < ".$ll_max->y;
  $_q = $mysqli->query($qsz) or trigger_error($mysqli->error);
  if ($_q !== FALSE) { $count = current($_q->fetch_row()); $alert = ceil($count / 25); $_q->free(); }

  if (preg_match('/^netname: *(.*)$/im', $r['whois'], $matches) == 1) $netname = $matches[1];

  if (is_null($r['address'])) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://maps.googleapis.com/maps/api/geocode/json?latlng='.$r['lat'].','.$r['lng'].'&sensor=false');
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $json = curl_exec($ch); $addr = json_decode($json);
    curl_close($ch);

    if ($addr->status == 'OK') {
      $r['address'] = $addr->results[0]->formatted_address;
      $r['address_type'] = implode(',', $addr->results[0]->types);
      $r['address_time'] = date('Y-m-d H:i:s');

      $qsz  = "UPDATE `help` SET";
      $qsz .= " `address` = '".$mysqli->real_escape_string($r['address'])."'";
      $qsz .= ",`address_type` = '".$mysqli->real_escape_string($r['address_type'])."'";
      $qsz .= ",`address_time` = '".$r['address_time']."'";
      $qsz .= " WHERE `id` = ".$r['id']." LIMIT 1";
      $mysqli->query($qsz) or trigger_error($mysqli->error);
    }
  }
?>
              <tr<?= ($r['indanger'] == 0 ? ' class="text-muted"' : '') ?>>
                <td><?= $r['datetime'] ?></td>
                <td><?= htmlentities($r['name']) ?></td>
                <td><?= htmlentities($r['phone']) ?></td>
                <td><?= htmlentities($r['address']) ?></td>
                <td><a href="map.php?id=<?= $r['id'] ?>"><?= number_format($r['lat'],6).','.number_format($r['lng'],6) ?></a></td>
                <td style="text-align:right;"><?= round($r['accuracy']) ?> m.</td>
                <td title="<?= $count ?>" class="<?= ($r['indanger'] == 0 ? 'text-muted' : 'text-danger') ?>"><?php for($i = 1; $i <= $alert; $i++) { echo '<span class="glyphicon glyphicon-exclamation-sign"></span> '; } ?></td>
                <td style="text-align:right;"><?= (!is_null($r['battery']) ? $r['battery'].'%' : '') ?></td>
                <td><?= htmlentities($r['infos']) ?></td>
                <td>
<?php
$u = array();
if ($r['urgence'] & 1) $u[] = 'Incendie';
if ($r['urgence'] & 2) $u[] = 'Accident de la route';
if ($r['urgence'] & 4) $u[] = 'Blessure';
if ($r['urgence'] & 8) $u[] = 'Attentat';
echo htmlentities(implode(', ', $u));
?>
                </td>
                <td style="text-align:center;"><?= ($r['indanger'] == 1 ? '' : '<span style="color:#f00;font-weight:bold;">&times</span>') ?></td>
                <td><?= htmlentities($netname) ?></td>
                <td><?= (!is_null($r['ip_forwarded']) ? $r['ip_forwarded'] : $r['ip']) ?></td>
              </tr>
<?php
}
$q->free();
$mysqli->close();
?>
            </tbody>
          </table>

        </div>
      </div>
    </div>
  </body>
</html>